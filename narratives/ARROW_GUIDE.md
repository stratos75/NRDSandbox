# Using Arrow with NRDSandbox - Complete Guide

## 🎯 **Quick Start**

1. **Create Story**: Go to https://mhgolkar.github.io/Arrow/v3
2. **Export HTML**: Use Arrow's export feature
3. **Import**: Upload to http://localhost:8000/narratives/
4. **Play**: Story integrates with your card game!

---

## 🌐 **Arrow Web Editor**

### **Access**
- **Online Editor**: https://mhgolkar.github.io/Arrow/v3
- **Documentation**: https://mhgolkar.github.io/Arrow/
- **GitHub**: https://github.com/mhgolkar/Arrow

### **Features**
- ✅ Visual node-and-link editor
- ✅ 100% no-coding approach
- ✅ Built-in testing and debugging
- ✅ One-click HTML export
- ✅ Works directly in browser

---

## 🎮 **NRDSandbox Integration**

### **What Gets Imported**
- **Story nodes** → Interactive dialogue
- **Choices** → Decision points that affect gameplay
- **Variables** → Persistent story state
- **Conditions** → Dynamic story branching

### **What Gets Enhanced**
- **Card rewards** based on story choices
- **Equipment bonuses** from narrative decisions
- **Stat modifications** from story outcomes
- **Audio narration** with existing "old man" voice

---

## 📝 **Creating Your First Story**

### **Basic Structure**
```
Start Node
├── Choice 1: Scout ahead
│   └── Reward: Stealth cards bias
├── Choice 2: Charge forward  
│   └── Reward: Weapon cards bias
└── Choice 3: Defensive position
    └── Reward: Armor cards bias
```

### **NRDSandbox-Specific Tips**

#### **Choice Text Keywords**
- Use **"weapon"** in choice text → Biases toward weapon cards
- Use **"armor"** in choice text → Biases toward armor cards  
- Use **"stealth"** in choice text → Biases toward special cards
- Use **"magic"** in choice text → Biases toward spell cards

#### **Story Variables**
- Use variables to track player decisions
- Variables persist across game sessions
- Can affect future story branches

#### **Node Naming**
- Use descriptive node IDs (e.g., "scout_path", "combat_victory")
- Helps with debugging and analytics

---

## 🔄 **Workflow Steps**

### **1. Story Creation in Arrow**
1. **Open** Arrow web editor
2. **Create nodes** for your narrative beats
3. **Add choices** that branch the story
4. **Set variables** to track decisions
5. **Test** your story within Arrow

### **2. Export from Arrow**
1. **Click export** in Arrow menu
2. **Choose HTML format**
3. **Download** the exported file
4. **Note the filename** for upload

### **3. Import to NRDSandbox**
1. **Visit** http://localhost:8000/narratives/
2. **Use upload form** to select Arrow HTML file
3. **Click import** - system processes automatically
4. **Check** story appears in available stories list

### **4. Test Integration**
1. **Click "Play Story"** from story list
2. **Make choices** and see effects
3. **Check** card rewards and game integration
4. **Verify** progress saves between sessions

---

## 🎨 **Advanced Features**

### **Card Reward Mapping**
Create custom reward logic by using these choice patterns:

```javascript
// In Arrow, choices with these texts trigger special rewards:
"Take the legendary sword" → Epic weapon card
"Don heavy armor" → Rare armor card  
"Learn ancient magic" → Spell card pack
"Master stealth techniques" → Special attack cards
```

### **Stat Modifications**
Story choices can modify mech stats:

```javascript
// Brave choices boost attack
"Charge fearlessly" → +5 ATK

// Cautious choices boost defense  
"Set up barriers" → +5 DEF

// Strategic choices boost both
"Coordinate attack" → +3 ATK, +3 DEF
```

### **Equipment Rewards**
Force-equip specific items based on story:

```javascript
// Story can auto-equip items
"Find ancient weapon" → Auto-equip legendary weapon
"Discover mystic armor" → Auto-equip protective gear
```

---

## 🐛 **Troubleshooting**

### **Import Issues**
- **File too large**: Arrow exports should be under 5MB
- **Invalid format**: Ensure you exported HTML from Arrow
- **Processing failed**: Check that story has valid structure

### **Story Not Loading**
- **Check database**: Ensure story appears in metadata table
- **Check JSON**: Verify story data file exists in `/narratives/data/`
- **Check nodes**: Ensure story has at least a "start" node

### **Choice Effects Not Working**
- **Check keywords**: Use recognized keywords in choice text
- **Check session**: Ensure user is logged in
- **Check database**: Verify story_rewards table exists

### **Audio Not Playing**
- **Check browser**: Ensure audio is not muted
- **Check files**: Verify audio files exist in `/data/audio/oldman/`
- **Check events**: Story events should trigger narrative responses

---

## 📊 **Story Analytics**

### **Tracking Available**
- **Choice selections** → Which options players choose
- **Story completion** → How many finish the story
- **Reward distribution** → What cards/equipment are earned
- **Session data** → Time spent in stories

### **Database Tables**
- `story_choices` → Individual choice tracking
- `story_progress` → User progress through stories
- `story_rewards` → Items earned from stories
- `story_analytics` → Aggregated analytics data

---

## 🚀 **Best Practices**

### **Story Design**
- **Keep initial stories short** (5-10 nodes)
- **Test thoroughly** in Arrow before export
- **Use meaningful choice text** for reward triggering
- **Balance** different paths with appropriate rewards

### **Choice Writing**
- **Be specific** about actions (helps with auto-rewards)
- **Use keywords** that match your card types
- **Consider consequences** for gameplay balance
- **Make choices feel meaningful**

### **Technical**
- **Save frequently** in Arrow (auto-save may not work in web)
- **Test exports** with small stories first
- **Keep backups** of your Arrow project files
- **Version control** story files if working with others

---

## 🎉 **Example Story Ideas**

### **Tactical Mission Brief**
- Pre-combat story that affects starting equipment
- Choices determine mission approach and card rewards
- Multiple paths leading to same combat scenario

### **Equipment Discovery**
- Finding legendary gear through exploration choices
- Story choices determine which equipment is discovered
- Can auto-equip or add to player collection

### **Character Background**
- Player backstory that affects available strategies
- Choices unlock different combat styles/cards
- Persistent effects that carry across multiple battles

### **Post-Combat Debrief**
- Story based on battle outcomes (win/loss)
- Choices affect experience gained and future opportunities
- Can modify stats or unlock new content

---

## 📞 **Support**

### **NRDSandbox Integration**
- Use the test interface: `/narratives/test-api.php`
- Check database with: `/narratives/setup-database.php`
- View logs in browser console

### **Arrow Editor**
- Official documentation: https://mhgolkar.github.io/Arrow/
- GitHub issues: https://github.com/mhgolkar/Arrow/issues
- Community examples in the repository

---

**Happy storytelling! Your tactical card battles just got a whole lot more narrative.** 🎮📖