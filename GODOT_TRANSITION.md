# 🎮 Godot Transition Guide

This document outlines the architectural decisions, data models, and game mechanics implemented in the NRDSandbox PHP prototype that should inform the Godot game engine implementation.

---

## 🎯 Core Game Design

### Game Philosophy
- **Tactical focus**: Every decision matters, no random chaos
- **Equipment synergy**: Reward strategic equipment combinations
- **Balanced progression**: Common cards accessible, rare cards meaningful
- **Audio-driven immersion**: Professional narration enhances engagement

### Target Audience
- **Strategy gamers** who enjoy deck building and tactical combat
- **Card game enthusiasts** looking for deeper mechanical complexity
- **Players seeking** balanced, skill-based gameplay with progression

---

## 🗄 Data Architecture

### Card System Schema
```sql
-- Core card structure
cards {
    id: TEXT (Primary Key)
    name: TEXT 
    type: ENUM('weapon', 'armor', 'special attack', 'spell', 'creature', 'support')
    element: ENUM('fire', 'ice', 'poison', 'plasma', 'neutral')
    rarity_id: INTEGER (Foreign Key)
    cost: INTEGER
    damage: INTEGER
    defense: INTEGER
    description: TEXT
    special_effect: TEXT
    image_path: TEXT
}

-- Rarity distribution system
card_rarities {
    rarity_name: TEXT ('common', 'uncommon', 'rare', 'epic', 'legendary')
    rarity_weight: REAL (60.0, 25.0, 12.0, 2.5, 0.5)
    max_copies_in_deck: INTEGER (6, 4, 3, 2, 1)
    power_multiplier: REAL (1.0, 1.15, 1.3, 1.5, 2.0)
    color_hex: TEXT (UI theming)
}
```

### Godot Implementation Strategy
```gdscript
# Card Resource Class
class_name Card extends Resource

@export var id: String
@export var card_name: String
@export var type: CardType
@export var element: Element
@export var rarity: CardRarity
@export var cost: int
@export var damage: int
@export var defense: int
@export var description: String
@export var special_effect: String
@export var texture: Texture2D

enum CardType { WEAPON, ARMOR, SPECIAL_ATTACK, SPELL, CREATURE, SUPPORT }
enum Element { FIRE, ICE, POISON, PLASMA, NEUTRAL }
enum CardRarity { COMMON, UNCOMMON, RARE, EPIC, LEGENDARY }
```

---

## ⚔️ Combat Mechanics

### Synergy System
**Current Implementation:**
- 2-piece elemental matching: 8% damage bonus + status effect
- 3-piece elemental matching: 15% damage bonus + enhanced status effect

**Godot Implementation:**
```gdscript
class_name SynergyCalculator

func calculate_synergy(equipped_cards: Array[Card]) -> SynergyBonus:
    var elements = {}
    for card in equipped_cards:
        if card.element != Element.NEUTRAL:
            elements[card.element] = elements.get(card.element, 0) + 1
    
    for element in elements:
        var count = elements[element]
        if count >= 3:
            return SynergyBonus.new(element, 15, StatusEffect.ENHANCED)
        elif count >= 2:
            return SynergyBonus.new(element, 8, StatusEffect.BASIC)
    
    return null
```

### Status Effects System
**Implemented Effects:**
- **Poison**: 5 damage per turn for 3 turns
- **Burn**: 3 damage per turn for 4 turns  
- **Freeze**: Skip next turn + slow movement
- **Energy Drain**: Reduce available energy

**Godot State Machine:**
```gdscript
class_name StatusEffect extends Node

@export var effect_type: EffectType
@export var duration: int
@export var value: int
@export var stacks: int = 1

func apply_effect(target: CombatUnit):
    match effect_type:
        EffectType.POISON:
            target.take_damage(value * stacks)
        EffectType.FREEZE:
            target.skip_turn = true
        # etc.

func tick_duration():
    duration -= 1
    return duration <= 0
```

### Damage Calculation
**Current Formula:**
```php
final_damage = base_damage * synergy_multiplier * vulnerability_multiplier * (1 - resistance)
```

**Godot Implementation:**
```gdscript
class_name DamageCalculator

func calculate_damage(attacker: CombatUnit, defender: CombatUnit, card: Card) -> int:
    var base_damage = card.damage + attacker.attack_bonus
    var synergy_mult = attacker.get_synergy_multiplier()
    var vuln_mult = defender.get_vulnerability_multiplier(card.element)
    var resistance = defender.get_resistance(card.element)
    
    return int(base_damage * synergy_mult * vuln_mult * (1.0 - resistance))
```

---

## 🎲 Card Distribution System

### Rarity-Weighted Dealing
**PHP Algorithm:**
```php
// Weighted random selection based on rarity percentages
function drawWeightedRandomCard($excludeIds, $cardsByRarity) {
    $totalWeight = calculate_total_weight($cardsByRarity);
    $random = mt_rand(1, intval($totalWeight * 100)) / 100;
    
    foreach ($cardsByRarity as $rarity => $data) {
        $currentWeight += $data['weight'];
        if ($random <= $currentWeight) {
            return select_random_card_from_rarity($data['cards']);
        }
    }
}
```

**Godot Implementation:**
```gdscript
class_name CardDealer

var rarity_weights = {
    CardRarity.COMMON: 60.0,
    CardRarity.UNCOMMON: 25.0,
    CardRarity.RARE: 12.0,
    CardRarity.EPIC: 2.5,
    CardRarity.LEGENDARY: 0.5
}

func draw_balanced_hand(hand_size: int) -> Array[Card]:
    var hand = []
    var used_ids = []
    
    # Guarantee one of each equipment type first
    hand.append(draw_card_by_type(Card.Type.WEAPON, used_ids))
    hand.append(draw_card_by_type(Card.Type.ARMOR, used_ids))
    hand.append(draw_card_by_type(Card.Type.SPECIAL_ATTACK, used_ids))
    
    # Fill remaining slots with weighted random
    while hand.size() < hand_size:
        var card = draw_weighted_random_card(used_ids)
        if card:
            hand.append(card)
            used_ids.append(card.id)
    
    return hand
```

---

## 🎵 Audio Integration

### Event-Driven Narration
**Current Events:**
- Game start/end
- Equipment actions
- Combat actions
- Turn transitions
- Victory/defeat

**Godot Audio Manager:**
```gdscript
class_name NarrativeAudioManager extends AudioStreamPlayer

var audio_clips = {}
var is_muted = false
var current_clip: AudioStream

func trigger_narrative(event: String, delay: float = 0.0):
    if is_muted or not audio_clips.has(event):
        return
    
    if delay > 0.0:
        await get_tree().create_timer(delay).timeout
    
    if current_clip and is_playing():
        stop()  # Prevent overlapping clips
    
    current_clip = audio_clips[event]
    stream = current_clip
    play()

# Usage in combat
func on_weapon_equipped():
    audio_manager.trigger_narrative("weapon_equipped", 0.5)
```

### Audio File Structure
```
audio/
├── narrator/
│   ├── combat_start.ogg
│   ├── weapon_equipped.ogg
│   ├── synergy_achieved.ogg
│   ├── victory.ogg
│   └── defeat.ogg
├── sfx/
│   ├── card_draw.ogg
│   ├── equipment_equip.ogg
│   └── damage_dealt.ogg
└── music/
    ├── menu_theme.ogg
    └── battle_theme.ogg
```

---

## 🎨 UI/UX Architecture

### Equipment System Interface
**Design Principles:**
- **Visual feedback**: Immediate response to player actions
- **Clear hierarchy**: Equipment slots clearly separated from hand
- **Synergy indication**: Visual cues for matching elements
- **Accessibility**: Color-blind friendly indicators

**Godot UI Structure:**
```
BattlefieldUI (Control)
├── EquipmentPanel (VBoxContainer)
│   ├── WeaponSlot (CardSlot)
│   ├── ArmorSlot (CardSlot)
│   └── SpecialSlot (CardSlot)
├── HandPanel (HBoxContainer)
│   └── CardHand (Array of CardUI)
├── SynergyDisplay (Control)
│   └── ElementBonuses (VBoxContainer)
└── CombatLog (RichTextLabel)
```

### Card Slot Component
```gdscript
class_name CardSlot extends Control

@export var slot_type: Card.Type
@export var equipped_card: Card
@onready var card_display = $CardDisplay
@onready var synergy_indicator = $SynergyIndicator

func can_accept_card(card: Card) -> bool:
    return card.type == slot_type

func equip_card(card: Card):
    equipped_card = card
    card_display.setup_card(card)
    check_synergy()
    
func check_synergy():
    var synergy = SynergyCalculator.get_synergy_for_slot(self)
    synergy_indicator.display_synergy(synergy)
```

---

## 🔄 State Management

### Game State Architecture
**Current PHP Session Model:**
```php
$_SESSION['playerMech'] = ['HP' => 75, 'ATK' => 30, 'DEF' => 15];
$_SESSION['playerEquipment'] = ['weapon' => null, 'armor' => null];
$_SESSION['player_hand'] = [/* card objects */];
$_SESSION['currentTurn'] = 1;
```

**Godot State Manager:**
```gdscript
class_name GameState extends RefCounted

var player_mech: MechData
var enemy_mech: MechData
var player_equipment: EquipmentSet
var enemy_equipment: EquipmentSet
var player_hand: Array[Card]
var enemy_hand: Array[Card]
var current_turn: int
var status_effects: Array[StatusEffect]

signal state_changed(property: String, new_value: Variant)

func update_mech_hp(is_player: bool, new_hp: int):
    if is_player:
        player_mech.hp = new_hp
    else:
        enemy_mech.hp = new_hp
    state_changed.emit("mech_hp", new_hp)
```

### Save System Design
```gdscript
class_name SaveSystem

const SAVE_FILE = "user://savegame.save"

func save_game(game_state: GameState):
    var save_data = {
        "player_mech": game_state.player_mech.to_dict(),
        "player_equipment": game_state.player_equipment.to_dict(),
        "player_hand": cards_to_array(game_state.player_hand),
        "statistics": game_state.statistics.to_dict(),
        "timestamp": Time.get_unix_time_from_system()
    }
    
    var save_file = FileAccess.open(SAVE_FILE, FileAccess.WRITE)
    save_file.store_string(JSON.stringify(save_data))
    save_file.close()
```

---

## 🎛 Configuration System

### Game Rules Configuration
```gdscript
class_name GameConfig extends Resource

@export var starting_hand_size: int = 5
@export var max_hand_size: int = 7
@export var deck_size: int = 20
@export var cards_drawn_per_turn: int = 1
@export var starting_player_hp: int = 75
@export var starting_enemy_hp: int = 75

# Synergy configuration
@export var two_piece_synergy_bonus: float = 0.08
@export var three_piece_synergy_bonus: float = 0.15

# Status effect durations
@export var poison_duration: int = 3
@export var burn_duration: int = 4
@export var freeze_duration: int = 1
```

---

## 🏗 Development Priorities

### Phase 1: Core Systems
1. **Card Resource System** - Implement Card class and CardDatabase
2. **Basic Combat** - Turn-based mech vs mech combat
3. **Equipment System** - Weapon/armor/special attack slots
4. **Hand Management** - Draw, play, discard mechanics

### Phase 2: Advanced Features
1. **Synergy System** - Elemental matching bonuses
2. **Status Effects** - DoT, buffs, debuffs with duration
3. **Audio Integration** - Event-driven narration system
4. **UI Polish** - Animations, feedback, accessibility

### Phase 3: Content & Balance
1. **Card Expansion** - Implement all 16+ cards from prototype
2. **AI Opponent** - Smart equipment and card playing
3. **Progression System** - Unlock new cards, track statistics
4. **Multiplayer Framework** - Architecture for player vs player

### Phase 4: Production Polish
1. **Save System** - Persistent progress and statistics
2. **Settings Menu** - Audio, graphics, accessibility options
3. **Tutorial System** - Interactive learning experience
4. **Performance Optimization** - 60fps on target platforms

---

## 🔧 Technical Considerations

### Performance Targets
- **60 FPS** on mid-range hardware
- **Fast startup** times (<3 seconds to main menu)
- **Responsive UI** (<100ms input lag)
- **Memory efficient** card rendering

### Platform Strategy
- **Primary**: Desktop (Windows, Mac, Linux)
- **Secondary**: Mobile (Android, iOS) with adapted UI
- **Future**: Web platform via Godot 4 web export

### Code Architecture Patterns
- **Observer Pattern** for game state changes
- **Command Pattern** for undoable actions
- **State Machine** for turn management
- **Resource System** for data-driven design

---

## 📊 Analytics & Metrics

### Key Performance Indicators
- **Card Usage Rates** - Which cards are most/least played
- **Synergy Frequency** - How often players achieve bonuses
- **Game Duration** - Average time per battle
- **Player Retention** - Session length and return rate

### Godot Analytics Integration
```gdscript
class_name GameAnalytics extends Node

func track_card_played(card: Card, turn: int):
    var event_data = {
        "card_id": card.id,
        "card_type": Card.Type.keys()[card.type],
        "turn_number": turn,
        "synergy_active": has_synergy()
    }
    # Send to analytics service
    
func track_game_end(won: bool, duration: float):
    var event_data = {
        "victory": won,
        "duration_seconds": duration,
        "cards_played": total_cards_played,
        "synergy_count": synergy_activations
    }
    # Send to analytics service
```

---

## 🎯 Success Metrics

### Gameplay Goals
- **Strategic Depth**: Multiple viable strategies and counter-play
- **Learning Curve**: Easy to learn, difficult to master
- **Replayability**: High variety in equipment combinations
- **Balance**: No dominant strategies, all rarities useful

### Technical Goals
- **Stability**: <1% crash rate in production
- **Performance**: Consistent 60fps on target hardware
- **Accessibility**: Full keyboard navigation, colorblind support
- **Localization**: Architecture supports multiple languages

---

**🚀 This prototype provides a solid foundation for creating a full-featured tactical card battle game in Godot. The database-driven design, balanced mechanics, and professional audio system demonstrate the potential for a compelling gaming experience.**