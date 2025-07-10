/**
 * Story Integration System for NRDSandbox Arrow Narrative Support
 * Extends the existing NarrativeGuide with decision tree capabilities
 */

// Extend NarrativeGuide with story support
const StoryNarrative = {
    // Story-specific properties
    storyManager: null,
    currentStory: null,
    storyMode: false,
    choiceModal: null,
    storyPanel: null,
    
    /**
     * Initialize story narrative system
     */
    init: function() {
        console.log('📖 Initializing Story Narrative System...');
        
        // Create story UI elements
        this.createStoryPanel();
        this.createChoiceModal();
        
        // Extend NarrativeGuide with story events
        this.extendNarrativeGuide();
        
        console.log('✅ Story Narrative System initialized');
    },
    
    /**
     * Create story panel UI
     */
    createStoryPanel: function() {
        const panel = document.createElement('div');
        panel.id = 'storyPanel';
        panel.className = 'story-panel';
        panel.innerHTML = `
            <div class="story-header">
                <h3 id="storyTitle">Story Mode</h3>
                <button id="storyCloseBtn" class="story-close" onclick="StoryNarrative.exitStory()">×</button>
            </div>
            <div class="story-content">
                <div id="storyText" class="story-text"></div>
                <div id="storyChoices" class="story-choices"></div>
            </div>
            <div class="story-progress">
                <div id="storyProgressBar" class="story-progress-bar"></div>
                <span id="storyProgressText">Node 1 of 5</span>
            </div>
        `;
        
        document.body.appendChild(panel);
        this.storyPanel = panel;
    },
    
    /**
     * Create choice modal for story decisions
     */
    createChoiceModal: function() {
        const modal = document.createElement('div');
        modal.id = 'choiceModal';
        modal.className = 'choice-modal';
        modal.innerHTML = `
            <div class="choice-overlay" onclick="StoryNarrative.hideChoiceModal()"></div>
            <div class="choice-container">
                <div class="choice-header">
                    <h4>Make Your Choice</h4>
                </div>
                <div id="choiceContent" class="choice-content"></div>
                <div class="choice-buttons">
                    <button id="choiceBackBtn" class="choice-back" onclick="StoryNarrative.goBack()">Back</button>
                    <button id="choiceConfirmBtn" class="choice-confirm" onclick="StoryNarrative.confirmChoice()">Confirm</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        this.choiceModal = modal;
    },
    
    /**
     * Extend NarrativeGuide with story events
     */
    extendNarrativeGuide: function() {
        // Store original trigger function
        const originalTrigger = NarrativeGuide.trigger;
        
        // Override trigger to handle story events
        NarrativeGuide.trigger = function(eventName, data = {}) {
            // Check if this is a story event
            if (eventName.startsWith('story_')) {
                return StoryNarrative.handleStoryEvent(eventName, data);
            }
            
            // Call original trigger for non-story events
            return originalTrigger.call(this, eventName, data);
        };
        
        // Add story-specific audio events
        this.addStoryAudioEvents();
    },
    
    /**
     * Add story-specific audio events to NarrativeGuide
     */
    addStoryAudioEvents: function() {
        // Add story events to the NarrativeGuide event processing
        const originalProcessEvent = NarrativeGuide._processEvent;
        
        NarrativeGuide._processEvent = function(eventName, data = {}) {
            // Handle story events
            switch (eventName) {
                case 'story_start':
                    this._display('thoughtful', 'A new tale begins... Listen carefully, for every choice matters.');
                    this.playAudio('story_start');
                    break;
                    
                case 'story_choice':
                    this._display('serious', 'Choose wisely, recruit. Your decision will shape the path ahead.');
                    this.playAudio('story_choice');
                    break;
                    
                case 'story_reward':
                    this._display('happy', 'Well done! Your choices have earned you a reward.');
                    this.playAudio('story_reward');
                    break;
                    
                case 'story_consequence':
                    this._display('disappointed', 'Every action has consequences. Remember this lesson.');
                    this.playAudio('story_consequence');
                    break;
                    
                case 'story_end':
                    this._display('thoughtful', 'And so your tale comes to an end. But the battlefield awaits...');
                    this.playAudio('story_end');
                    break;
                    
                default:
                    // Call original for non-story events
                    return originalProcessEvent.call(this, eventName, data);
            }
        };
    },
    
    /**
     * Handle story-specific events
     */
    handleStoryEvent: function(eventName, data = {}) {
        console.log('📖 Story Event:', eventName, data);
        
        switch (eventName) {
            case 'story_node_changed':
                this.updateStoryDisplay(data.node);
                break;
                
            case 'story_choice_made':
                this.processChoice(data.choice, data.effects);
                break;
                
            case 'story_reward_granted':
                this.showReward(data.reward);
                break;
                
            case 'story_variable_changed':
                this.updateStoryVariables(data.variables);
                break;
        }
    },
    
    /**
     * Start a story
     */
    startStory: function(storyId) {
        console.log('📖 Starting story:', storyId);
        
        // Enter story mode
        this.storyMode = true;
        this.showStoryPanel();
        
        // Load story via AJAX
        this.loadStory(storyId);
        
        // Trigger story start narration
        NarrativeGuide.trigger('story_start');
    },
    
    /**
     * Load story data
     */
    loadStory: function(storyId) {
        fetch('/narratives/story-api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'load_story',
                story_id: storyId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.currentStory = data.story;
                this.updateStoryDisplay(data.current_node);
                this.updateStoryProgress();
            } else {
                console.error('Failed to load story:', data.error);
                this.showError('Failed to load story: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Story loading error:', error);
            this.showError('Network error loading story');
        });
    },
    
    /**
     * Update story display
     */
    updateStoryDisplay: function(nodeData) {
        if (!nodeData) return;
        
        const storyText = document.getElementById('storyText');
        const storyChoices = document.getElementById('storyChoices');
        const storyTitle = document.getElementById('storyTitle');
        
        // Update title
        if (this.currentStory && this.currentStory.title) {
            storyTitle.textContent = this.currentStory.title;
        }
        
        // Update text with typewriter effect
        this.typewriterText(storyText, nodeData.content);
        
        // Update choices
        this.updateChoices(storyChoices, nodeData.choices);
        
        // Update progress
        this.updateStoryProgress();
        
        // Trigger narrative response
        if (nodeData.content) {
            NarrativeGuide.trigger('story_choice');
        }
    },
    
    /**
     * Update story choices
     */
    updateChoices: function(container, choices) {
        container.innerHTML = '';
        
        if (!choices || choices.length === 0) {
            // End of story
            const endButton = document.createElement('button');
            endButton.className = 'story-choice story-end';
            endButton.textContent = 'End Story';
            endButton.onclick = () => this.exitStory();
            container.appendChild(endButton);
            return;
        }
        
        choices.forEach((choice, index) => {
            const button = document.createElement('button');
            button.className = 'story-choice';
            button.textContent = choice.text;
            button.onclick = () => this.makeChoice(index);
            
            // Add visual indicators for choice effects
            if (choice.nrd_effects && choice.nrd_effects.length > 0) {
                const indicator = document.createElement('span');
                indicator.className = 'choice-indicator';
                indicator.textContent = '⚡';
                indicator.title = 'This choice will affect the game';
                button.appendChild(indicator);
            }
            
            container.appendChild(button);
        });
    },
    
    /**
     * Make a story choice
     */
    makeChoice: function(choiceIndex) {
        console.log('📖 Making choice:', choiceIndex);
        
        // Disable all choice buttons
        const choiceButtons = document.querySelectorAll('.story-choice');
        choiceButtons.forEach(btn => btn.disabled = true);
        
        // Send choice to server
        fetch('/narratives/story-api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'make_choice',
                choice_index: choiceIndex
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update display with new node
                this.updateStoryDisplay(data.node_data);
                
                // Process effects
                this.processChoiceEffects(data.effects);
                
                // Trigger narrative response
                NarrativeGuide.trigger('story_choice_made', {
                    choice: choiceIndex,
                    effects: data.effects
                });
            } else {
                console.error('Choice failed:', data.error);
                this.showError('Choice failed: ' + data.error);
                
                // Re-enable buttons
                choiceButtons.forEach(btn => btn.disabled = false);
            }
        })
        .catch(error => {
            console.error('Choice error:', error);
            this.showError('Network error making choice');
            
            // Re-enable buttons
            choiceButtons.forEach(btn => btn.disabled = false);
        });
    },
    
    /**
     * Process choice effects
     */
    processChoiceEffects: function(effects) {
        if (!effects) return;
        
        // Handle rewards
        if (effects.rewards && effects.rewards.length > 0) {
            effects.rewards.forEach(reward => {
                this.showReward(reward);
            });
        }
        
        // Handle narrative triggers
        if (effects.narrative && effects.narrative.length > 0) {
            effects.narrative.forEach(narrative => {
                NarrativeGuide.trigger(narrative.event, { text: narrative.text });
            });
        }
        
        // Handle stat modifications
        if (effects.stat_mods) {
            this.showStatModifications(effects.stat_mods);
        }
    },
    
    /**
     * Show reward notification
     */
    showReward: function(reward) {
        console.log('🎁 Reward received:', reward);
        
        // Create reward notification
        const notification = document.createElement('div');
        notification.className = 'story-reward-notification';
        notification.innerHTML = `
            <div class="reward-icon">🎁</div>
            <div class="reward-text">
                <strong>Reward!</strong><br>
                ${reward.type === 'card' ? 'New Card: ' + reward.card_id : 'Unknown reward'}
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
        
        // Trigger narrative response
        NarrativeGuide.trigger('story_reward');
    },
    
    /**
     * Show stat modifications
     */
    showStatModifications: function(statMods) {
        console.log('📊 Stat modifications:', statMods);
        
        Object.entries(statMods).forEach(([stat, value]) => {
            const notification = document.createElement('div');
            notification.className = 'story-stat-notification';
            notification.innerHTML = `
                <div class="stat-icon">${value > 0 ? '📈' : '📉'}</div>
                <div class="stat-text">
                    ${stat.toUpperCase()}: ${value > 0 ? '+' : ''}${value}
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-remove after 2 seconds
            setTimeout(() => {
                notification.remove();
            }, 2000);
        });
    },
    
    /**
     * Typewriter effect for story text
     */
    typewriterText: function(element, text) {
        element.innerHTML = '';
        let index = 0;
        const speed = 30; // milliseconds per character
        
        const typeNext = () => {
            if (index < text.length) {
                element.innerHTML += text.charAt(index);
                index++;
                setTimeout(typeNext, speed);
            }
        };
        
        typeNext();
    },
    
    /**
     * Update story progress indicator
     */
    updateStoryProgress: function() {
        if (!this.currentStory) return;
        
        const progressBar = document.getElementById('storyProgressBar');
        const progressText = document.getElementById('storyProgressText');
        
        // Calculate progress (simplified - could be enhanced)
        const totalNodes = Object.keys(this.currentStory.nodes || {}).length;
        const currentNodeIndex = 1; // Would need to track actual progress
        
        const percentage = (currentNodeIndex / totalNodes) * 100;
        
        if (progressBar) {
            progressBar.style.width = percentage + '%';
        }
        
        if (progressText) {
            progressText.textContent = `Node ${currentNodeIndex} of ${totalNodes}`;
        }
    },
    
    /**
     * Show story panel
     */
    showStoryPanel: function() {
        if (this.storyPanel) {
            this.storyPanel.classList.add('active');
            document.body.classList.add('story-active');
        }
    },
    
    /**
     * Hide story panel
     */
    hideStoryPanel: function() {
        if (this.storyPanel) {
            this.storyPanel.classList.remove('active');
            document.body.classList.remove('story-active');
        }
    },
    
    /**
     * Show choice modal
     */
    showChoiceModal: function() {
        if (this.choiceModal) {
            this.choiceModal.classList.add('active');
        }
    },
    
    /**
     * Hide choice modal
     */
    hideChoiceModal: function() {
        if (this.choiceModal) {
            this.choiceModal.classList.remove('active');
        }
    },
    
    /**
     * Exit story mode
     */
    exitStory: function() {
        console.log('📖 Exiting story mode');
        
        this.storyMode = false;
        this.currentStory = null;
        this.hideStoryPanel();
        this.hideChoiceModal();
        
        // Trigger narrative response
        NarrativeGuide.trigger('story_end');
    },
    
    /**
     * Show error message
     */
    showError: function(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'story-error';
        errorDiv.textContent = message;
        document.body.appendChild(errorDiv);
        
        setTimeout(() => {
            errorDiv.remove();
        }, 3000);
    },
    
    /**
     * Get available stories
     */
    getAvailableStories: function() {
        return fetch('/narratives/story-api.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'get_stories'
            })
        })
        .then(response => response.json());
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    StoryNarrative.init();
});

// Add global story functions for easy access
window.StoryNarrative = StoryNarrative;