<?php
/**
 * Template Name: Scripture Search Page
 * 
 * This template creates a standalone search page that connects to the LetGodBeTrue API
 */

// Get header
get_header();

// Get API URL from plugin settings if available
$api_url = get_option('letgod_chat_api_url', 'http://198.46.85.193:8888/api');

// Avada specific wrappers
?>
<section id="content" class="<?php echo esc_attr( apply_filters( 'fusion_content_class', '' ) ); ?>">
    <div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <div class="post-content">
            <!-- Search Interface Start -->
            <div class="scripture-search-page">
                <div class="header">
                    <h1>LetGodBeTrue - Scripture Search</h1>
                    <p>Search through church documents, scripture explanations, and resources</p>
                </div>

                <div class="search-container">
                    <form class="search-form" id="searchForm">
                        <input 
                            type="text" 
                            id="searchInput" 
                            class="search-input" 
                            placeholder="Enter your search query..." 
                            required
                            autocomplete="off"
                        >
                        <button type="submit" class="search-button">Advanced Search</button>
                    </form>
                    <div class="category-filters" style="margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; flex-wrap: wrap; max-width: 700px; margin-top: 10px; gap: 10px;">
                            <!-- checkboxes here -->
                            <label><input type="checkbox" name="category[]" value="Website" id="category-website"> Website Documents</label>
                            <label><input type="checkbox" name="category[]" value="Proverbs" id="category-proverbs"> Proverbs Commentaries</label>
                            <label style="opacity: 0.5; cursor: not-allowed;" title="Audio Sermons search is currently disabled"><input type="checkbox" name="category[]" value="Sermons" id="category-sermons" disabled> Audio Sermons</label>
                            <label><input type="checkbox" name="category[]" value="Sermon Outlines" id="category-sermon-outlines"> Sermon Outlines</label>
                            <label><input type="checkbox" name="category[]" value="Bible" id="category-bible"> Scripture Search</label>
                        </div>
                    </div>
                    <div class="search-tips">
                        <small>Tip: Leave your search phrase outside of double quotes to search for similar meanings. Put your search phrase inside double quotes to search for exact text matches only.</small>
                    </div>
                </div>

                <div class="loading" id="loadingIndicator">
                    <div class="loading-spinner"></div>
                    <p>Searching resources...</p>
                </div>

                <div class="error-message" id="errorMessage"></div>

                <div class="results-container" id="resultsContainer">
                    <div class="result-count" id="resultCount"></div>
                    <div id="resultsList"></div>
                </div>
            </div>
            <!-- Search Interface End -->
        </div>
    </div>
</section>

<style>
/* Base styles */
.scripture-search-page {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    line-height: 1.6;
    color: #333;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    position: relative;
    z-index: 10;
}

/* More CSS styles from the earlier template */
.scripture-search-page h1, 
.scripture-search-page h2, 
.scripture-search-page h3 {
    color: #0078BE;
}

/* Header section */
.scripture-search-page .header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e1e1e1;
}

/* Search form styles - updated to create a single cohesive unit */
.scripture-search-page .search-container {
    display: flex;
    flex-direction: column;
    max-width: 800px;
    margin: 0 auto 20px;
    background-color: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.scripture-search-page .search-form {
    display: flex;
    gap: 0; /* Remove gap between elements */
    margin-bottom: 10px;
    border-radius: 5px;
    overflow: hidden; /* Keep the rounded corners clean */
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.scripture-search-page .search-input {
    flex: 1;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-right: none; /* Remove right border */
    border-radius: 5px 0 0 5px; /* Round only left corners */
    font-size: 16px;
    height: 50px; /* Fixed height for better alignment */
}

.scripture-search-page .search-button {
    background-color: #0078BE;
    color: white;
    border: none;
    border-radius: 0 5px 5px 0; /* Round only right corners */
    padding: 0 20px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.2s;
    margin: 0; /* Remove any margin */
    height: 50px; /* Fixed height for better alignment */
    white-space: nowrap; /* Prevent text wrapping */
}

.scripture-search-page .search-button:hover {
    background-color: #006ba9;
}

/* Direct API button style */
.scripture-search-page .direct-api-button {
    background-color: #6c757d;
    color: white;
    border: none;
    border-radius: 5px;
    padding: 12px 0;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.2s;
    margin-top: 10px; /* Add margin to separate it */
    width: 100%; /* Make it full width */
    height: 50px; /* Fixed height to match search button */
}

.scripture-search-page .direct-api-button:hover {
    background-color: #5a6268;
}

/* Loading indicator */
.scripture-search-page .loading {
    display: none;
    text-align: center;
    margin: 20px 0;
    padding: 10px;
    background-color: #f9f9f9;
    border-radius: 4px;
}

.scripture-search-page .loading-spinner {
    border: 4px solid #f3f3f3;
    border-top: 4px solid #0078BE;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    animation: spin 1s linear infinite;
    margin: 0 auto;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Results section */
.scripture-search-page .results-container {
    max-width: 800px;
    margin: 0 auto;
}

.scripture-search-page .result-count {
    margin-bottom: 15px;
    font-weight: 500;
}

.scripture-search-page .result-item {
    background-color: white;
    padding: 20px;
    margin-bottom: 15px;
    border-radius: 8px;
    box-shadow: 0 1px 5px rgba(0, 0, 0, 0.08);
    transition: transform 0.2s;
    border-left: 4px solid #0078BE;
}

.scripture-search-page .result-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.12);
}

.scripture-search-page .result-title {
    margin-top: 0;
    margin-bottom: 5px;
    font-size: 18px;
    color: #0078BE;
}

.scripture-search-page .result-category {
    display: inline-block;
    background-color: #e6f2ff;
    color: #0077cc;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    margin-bottom: 10px;
}

.scripture-search-page .result-content {
    margin-bottom: 10px;
    font-size: 14px;
    overflow-wrap: break-word;
    word-wrap: break-word;
    hyphens: auto;
}

.scripture-search-page .result-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12px;
    color: #666;
}

.scripture-search-page .result-number {
    background-color: #f0f0f0;
    padding: 3px 7px;
    border-radius: 3px;
}

/* Hidden fragment ID - Added */
.scripture-search-page .fragment-id {
    color: white;
    user-select: all; /* Makes it easily selectable for troubleshooting */
}

/* Error messaging */
.scripture-search-page .error-message {
    background-color: #fff0f0;
    color: #e74c3c;
    padding: 15px;
    border-radius: 5px;
    margin: 20px auto;
    max-width: 800px;
    text-align: center;
    display: none;
}

/* Debug panel */
.scripture-search-page .debug-panel {
    margin-top: 30px;
    padding: 15px;
    background-color: #f8f9fa;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-family: monospace;
    font-size: 13px;
    display: none;
}

.scripture-search-page .debug-panel h3 {
    margin-top: 0;
    color: #333;
}

.scripture-search-page .debug-panel pre {
    max-height: 300px;
    overflow-y: auto;
    background-color: #f0f0f0;
    padding: 10px;
    border-radius: 4px;
}

/* Debug styling for development */
#resultsContainer {
    border: 1px dashed #ccc;
    padding: 15px;
    margin-top: 20px;
}

.debug-item {
    border: 2px solid red !important;
    background-color: #fff8f8 !important;
}

/* Responsiveness */
@media (max-width: 768px) {
    .scripture-search-page .search-form {
        flex-direction: column;
        gap: 10px;
    }

    .scripture-search-page .search-input {
        border-radius: 5px;
        border-right: 1px solid #ddd;
    }

    .scripture-search-page .search-button {
        border-radius: 5px;
        width: 100%;
    }
}
</style>

<script>
window.wimpy = {
  init: function() { console.log('Wimpy fallback initialized'); },
  createPlayer: function() {
    console.log('Wimpy player creation bypassed');
    return { play: function() {} };
  }
};

window.addEventListener('error', function(e) {
  if (e.target && e.target.tagName === 'SCRIPT') {
    const scriptSrc = e.target.src;
    if (scriptSrc.includes('wimpy.js')) {
      console.log('wimpy.js fallback initialized');
      e.preventDefault();
    }
  }
}, true);
</script>

<script>
const DEBUG_MODE = true;
const API_BASE_URL = '/search-proxy.php';
console.log("Script loaded. API URL:", API_BASE_URL);

const searchForm = document.getElementById('searchForm');
const searchInput = document.getElementById('searchInput');
const loadingIndicator = document.getElementById('loadingIndicator');
const errorMessage = document.getElementById('errorMessage');
const resultsContainer = document.getElementById('resultsContainer');
const resultCount = document.getElementById('resultCount');
const resultsList = document.getElementById('resultsList');

const websiteCheckbox = document.getElementById('category-website');
const proverbsCheckbox = document.getElementById('category-proverbs');
const sermonsCheckbox = document.getElementById('category-sermons');
const sermonOutlinesCheckbox = document.getElementById('category-sermon-outlines');
const bibleCheckbox = document.getElementById('category-bible');

// Map internal category names to display names
const categoryDisplayNames = {
    'Website': 'Website Documents',
    'Proverbs': 'Proverbs Commentaries',
    'Sermons': 'Audio Sermons',
    'Sermon Outlines': 'Sermon Outlines',
    'Bible': 'Scripture Search'
};

// Category URL parameter constants
const CATEGORY_PARAM = 'cat';
const CATEGORY_CODES = {
    'Website': 'w',
    'Proverbs': 'p', 
    'Sermons': 's',
    'Sermon Outlines': 'o',
    'Bible': 'b'
};
const CATEGORY_CODES_REVERSE = {
    'w': 'Website',
    'p': 'Proverbs',
    's': 'Sermons',
    'o': 'Sermon Outlines',
    'b': 'Bible'
};

// Enhanced console logger with timing
function logDebug(request, response) {
    if (!DEBUG_MODE) return;
    console.log("[DEBUG] API Request:", request);
    console.log("[DEBUG] API Response:", response);
}

// Performance tracking class with seconds display
class PerformanceTracker {
    constructor(operationName) {
        this.operationName = operationName;
        this.startTime = performance.now();
        this.checkpoints = [];
        this.metadata = {};
    }

    checkpoint(name) {
        const currentTime = performance.now();
        const elapsed = currentTime - this.startTime;
        const elapsedSeconds = elapsed / 1000; // Convert to seconds
        const checkpoint = {
            name: name,
            time: currentTime,
            elapsed: elapsed
        };
        this.checkpoints.push(checkpoint);
        console.log(`⏱️ ${this.operationName} - ${name}: ${elapsedSeconds.toFixed(3)}s`);
        return elapsed;
    }

    setMetadata(key, value) {
        this.metadata[key] = value;
    }

    finish() {
        const endTime = performance.now();
        const totalTime = endTime - this.startTime;
        const totalSeconds = totalTime / 1000; // Convert to seconds
        
        console.log(`\n📊 ${this.operationName} Performance Summary:`);
        console.log(`   Total Time: ${totalSeconds.toFixed(3)}s`); // Display in seconds
        
        if (this.checkpoints.length > 0) {
            console.log(`   Checkpoints:`);
            let previousTime = this.startTime;
            this.checkpoints.forEach((cp, index) => {
                const duration = cp.time - previousTime;
                const durationSeconds = duration / 1000; // Convert to seconds
                const percentage = (duration / totalTime * 100).toFixed(1);
                console.log(`     - ${cp.name}: ${durationSeconds.toFixed(3)}s (${percentage}%)`);
                previousTime = cp.time;
            });
        }
        
        if (Object.keys(this.metadata).length > 0) {
            console.log(`   Metadata:`);
            Object.entries(this.metadata).forEach(([key, value]) => {
                console.log(`     - ${key}: ${value}`);
            });
        }
        
        console.log(`\n`);
        return totalTime;
    }
}

function safeGet(obj, path, defaultValue = '') {
    return path.split('.').reduce((prev, curr) => {
        return prev && prev[curr] !== undefined ? prev[curr] : defaultValue;
    }, obj);
}

function getCategoryDisplayName(categoryName) {
    return categoryDisplayNames[categoryName] || categoryName;
}

function updateUrlWithCategories(query, categories) {
    if (!query) return;
    
    const newUrl = new URL(window.location.href);
    newUrl.searchParams.set('s', query);
    
    // Build category string
    const catString = categories.map(cat => CATEGORY_CODES[cat]).filter(Boolean).join('');
    if (catString) {
        newUrl.searchParams.set(CATEGORY_PARAM, catString);
    } else {
        newUrl.searchParams.delete(CATEGORY_PARAM);
    }
    
    window.history.pushState({}, '', newUrl.toString());
}

searchForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const query = searchInput.value.trim();
    const selectedCategories = Array.from(
        document.querySelectorAll('input[name="category[]"]:checked')
    ).map(el => el.value);

    if (query) {
        updateUrlWithCategories(query, selectedCategories);
        performSearch(query, selectedCategories);
    }
});

async function performSearch(query, categories = []) {
    // Create performance tracker for the entire search operation
    const searchTracker = new PerformanceTracker('Search Operation');
    
    resetUI();
    loadingIndicator.style.display = 'block';
    
    // Track UI setup
    searchTracker.checkpoint('UI Reset & Loading Display');
    
    // Get selected categories from checkboxes if not provided
    if (!categories || categories.length === 0) {
        categories = Array.from(
            document.querySelectorAll('input[name="category[]"]:checked')
        ).map(cb => cb.value);
    }

    // Prepare request body with metadata
    const requestBody = {
        query,
        webpage_search: true,
        categories: categories
        // Let backend determine if this is from main search
    };

    // Set metadata for tracking
    searchTracker.setMetadata('Query', query);
    searchTracker.setMetadata('Categories', categories.join(', ') || 'None');

    logDebug(requestBody, "Sending request...");
    console.log(`🔍 Starting search for: "${query}" with categories: ${JSON.stringify(categories)}`);

    try {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 60000);

        searchTracker.checkpoint('Request Preparation');

        // Time the API request
        const response = await fetch(`${API_BASE_URL}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Basic ' + btoa('anyone:superstrongpassword99')
            },
            body: JSON.stringify(requestBody),
            signal: controller.signal
        });

        clearTimeout(timeoutId);
        searchTracker.checkpoint('API Response Received');

        const responseText = await response.text();
        searchTracker.checkpoint('Response Text Read');
        
        logDebug(requestBody, responseText);

        // Time the JSON parsing
        let data = JSON.parse(responseText);
        searchTracker.checkpoint('JSON Parsed');
        
        // Check if backend applied default categories
        if (data.category_info) {
            console.log('Category info from backend:', data.category_info);
            
            // If backend applied defaults, update the UI checkboxes
            if (data.category_info.default_applied && data.category_info.selected) {
                // Update checkboxes to reflect backend selection
                document.querySelectorAll('input[name="category[]"]').forEach(checkbox => {
                    checkbox.checked = data.category_info.selected.includes(checkbox.value);
                });
                
                console.log('Updated UI checkboxes based on backend defaults');
            }
        }
        
        // Set results metadata
        const resultsCount = data.results ? data.results.length : 0;
        searchTracker.setMetadata('Results Count', resultsCount);
        searchTracker.setMetadata('Response Size', `${(responseText.length / 1024).toFixed(2)} KB`);
        
        // Time the display rendering
        displayResults(data);
        searchTracker.checkpoint('Results Displayed');
        
        // Finish tracking
        const totalTime = searchTracker.finish();
        const totalSeconds = totalTime / 1000;
        
        // Additional summary for search results
        console.log(`✅ Search completed successfully in ${totalSeconds.toFixed(3)}s with ${resultsCount} results`);
        
    } catch (err) {
        searchTracker.checkpoint('Error Occurred');
        const totalTime = searchTracker.finish();
        const totalSeconds = totalTime / 1000;
        
        // Log error with timing in seconds
        console.error(`❌ Search failed after ${totalSeconds.toFixed(3)}s`);
        console.error("Search error:", err);
        
        // Show detailed error information
        errorMessage.textContent = `Search failed: ${err.message}`;
        if (categories && categories.length > 0) {
            errorMessage.textContent += `. Selected categories: ${categories.join(", ")}`;
        } else {
            errorMessage.textContent += `. No categories selected.`;
        }
        
        if (err.response) {
            try {
                const errorDetails = await err.response.text();
                errorMessage.textContent += `\n\nServer response: ${errorDetails}`;
                console.error("Server response:", errorDetails);
            } catch (responseErr) {
                console.error("Could not read error response:", responseErr);
            }
        }
        
        errorMessage.style.display = 'block';
        resultCount.textContent = 'Search failed. See error message above.';
    } finally {
        loadingIndicator.style.display = 'none';
    }
}

// Extract quoted terms from a search query
function extractQuotedTerms(query) {
    const quotedTerms = [];
    let regex = /"([^"]*)"/g;
    let match;
    
    while ((match = regex.exec(query)) !== null) {
        if (match[1].trim() !== '') {
            quotedTerms.push(match[1].trim());
        }
    }
    
    return quotedTerms;
}

function displayResults(data) {
    const displayTracker = new PerformanceTracker('Display Rendering');
    
    resultsContainer.style.display = 'block';

    // Check if backend provided category info before we process results
    if (data && data.category_info) {
        console.log('Category info from backend:', data.category_info);
        
        // If backend applied defaults, update the UI checkboxes
        if (data.category_info.default_applied && data.category_info.selected) {
            // Update checkboxes to reflect backend selection
            document.querySelectorAll('input[name="category[]"]').forEach(checkbox => {
                checkbox.checked = data.category_info.selected.includes(checkbox.value);
            });
            
            console.log('Updated UI checkboxes based on backend defaults');
        }
    }

    if (data && data.success === true && Array.isArray(data.results)) {
        data = data.results;
    }

    if (!data || !Array.isArray(data) || data.length === 0) {
        resultCount.textContent = 'No results found. Try refining your search.';
        displayTracker.finish();
        return;
    }

    displayTracker.checkpoint('Data Validation');

    // Get the selected categories
    const selectedCategories = Array.from(
        document.querySelectorAll('input[name="category[]"]:checked')
    ).map(cb => cb.value);
    
    console.log(`Selected categories: ${selectedCategories}`);
    
    const resultsToDisplay = data;
    displayTracker.setMetadata('Total Results', resultsToDisplay.length);

    // Sort by score
    const sortedResults = [...resultsToDisplay].sort((a, b) => parseFloat(b.score) - parseFloat(a.score));
    displayTracker.checkpoint('Results Sorted');

    // Limit to 10 results
    const limitedResults = sortedResults.slice(0, 10);
    displayTracker.setMetadata('Displayed Results', limitedResults.length);

    console.log(`📋 Showing ${limitedResults.length} of ${resultsToDisplay.length} total results`);

    // Update total count text
    const totalFound = resultsToDisplay.length;
    const showing = limitedResults.length;
    let countText = '';

    if (totalFound > 50) {
        countText = `Showing ${showing} results found`;
    } else if (totalFound === 0) {
        countText = 'No results found';
    } else {
        countText = `Showing ${showing} results found`;
    }

    resultCount.textContent = countText;
    resultsList.innerHTML = '';

    displayTracker.checkpoint('Count Display Updated');

    // Get all quoted terms from the search query
    const searchQuery = searchInput.value.trim();
    const quotedTerms = extractQuotedTerms(searchQuery);

    // Track result item creation
    const itemCreationStart = performance.now();

    limitedResults.forEach((result, index) => {
        const documentName = safeGet(result, 'document_name', 'Untitled Document');
        const fragmentId = safeGet(result, 'fragment_id', 'Unknown');
        const categoryInternal = safeGet(result, 'category', 'Uncategorized');
        const categoryDisplay = getCategoryDisplayName(categoryInternal);
        const fragmentContent = safeGet(result, 'fragment_content', 'No Searchable Content');
        const sourceUrl = safeGet(result, 'source_url', `https://letgodbetrue2.com/search/?q=${encodeURIComponent(documentName)}`);
        
        let content = fragmentContent;
        content = content.replace(/^([.,?!:;])\s+/, '');

        // Bold all quoted terms in the content
        if (quotedTerms.length > 0) {
            quotedTerms.forEach(term => {
                const escapedTerm = term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                const regex = new RegExp('(' + escapedTerm + ')', 'gi');
                content = content.replace(regex, '<b>$1</b>');
            });
        }

        const resultNumber = index + 1;

        const resultItem = document.createElement('div');
        resultItem.className = 'result-item';
        resultItem.innerHTML = `
            <h3 class="result-title">
                <a href="${sourceUrl}" target="_blank">${documentName}</a>
            </h3>
            <span class="result-category">${categoryDisplay}</span>
            <div class="result-content">${content}</div>
            <div class="result-meta">
                <span class="fragment-id">Fragment ID: ${fragmentId}</span>
                <span class="result-number">#${resultNumber}</span>
            </div>
        `;

        resultsList.appendChild(resultItem);
    });

    const itemCreationTime = performance.now() - itemCreationStart;
    const itemCreationSeconds = itemCreationTime / 1000; // Convert to seconds
    displayTracker.setMetadata('Item Creation Time', `${itemCreationSeconds.toFixed(3)}s`);
    displayTracker.checkpoint('Result Items Created');

    displayTracker.finish();
}

function resetUI() {
    errorMessage.style.display = 'none';
    errorMessage.textContent = '';
    resultsList.innerHTML = '';
}

// ChatBot Performance Monitoring (if integrated)
class ChatBotPerformanceMonitor {
    constructor() {
        this.conversations = [];
        this.currentConversation = null;
    }

    startConversation() {
        this.currentConversation = {
            id: Date.now(),
            startTime: performance.now(),
            messages: []
        };
        console.log(`💬 New ChatBot conversation started (ID: ${this.currentConversation.id})`);
    }

    trackMessage(message, isUser = true) {
        if (!this.currentConversation) {
            this.startConversation();
        }

        const messageData = {
            timestamp: performance.now(),
            isUser: isUser,
            message: message,
            processingTime: null
        };

        if (!isUser && this.currentConversation.messages.length > 0) {
            const lastUserMessage = [...this.currentConversation.messages]
                .reverse()
                .find(m => m.isUser);
            if (lastUserMessage) {
                messageData.processingTime = messageData.timestamp - lastUserMessage.timestamp;
                const processingSeconds = messageData.processingTime / 1000; // Convert to seconds
                console.log(`⏱️ ChatBot Response Time: ${processingSeconds.toFixed(3)}s`);
            }
        }

        this.currentConversation.messages.push(messageData);
        
        if (!isUser) {
            this.logConversationStats();
        }
    }

    logConversationStats() {
        if (!this.currentConversation) return;

        const totalMessages = this.currentConversation.messages.length;
        const userMessages = this.currentConversation.messages.filter(m => m.isUser).length;
        const botMessages = totalMessages - userMessages;
        
        const responseTimes = this.currentConversation.messages
            .filter(m => !m.isUser && m.processingTime !== null)
            .map(m => m.processingTime);
        
        if (responseTimes.length > 0) {
            const avgResponseTime = responseTimes.reduce((a, b) => a + b, 0) / responseTimes.length;
            const maxResponseTime = Math.max(...responseTimes);
            const minResponseTime = Math.min(...responseTimes);
            
            // Convert all times to seconds
            const avgSeconds = avgResponseTime / 1000;
            const maxSeconds = maxResponseTime / 1000;
            const minSeconds = minResponseTime / 1000;
            const durationSeconds = (performance.now() - this.currentConversation.startTime) / 1000;
            
            console.log(`\n📊 ChatBot Conversation Stats (ID: ${this.currentConversation.id}):`);
            console.log(`   - Total Messages: ${totalMessages} (User: ${userMessages}, Bot: ${botMessages})`);
            console.log(`   - Avg Response Time: ${avgSeconds.toFixed(3)}s`);
            console.log(`   - Min Response Time: ${minSeconds.toFixed(3)}s`);
            console.log(`   - Max Response Time: ${maxSeconds.toFixed(3)}s`);
            console.log(`   - Conversation Duration: ${durationSeconds.toFixed(3)}s\n`);
        }
    }

    endConversation() {
        if (this.currentConversation) {
            this.currentConversation.endTime = performance.now();
            this.currentConversation.duration = this.currentConversation.endTime - this.currentConversation.startTime;
            const durationSeconds = this.currentConversation.duration / 1000; // Convert to seconds
            this.conversations.push(this.currentConversation);
            
            console.log(`💬 ChatBot conversation ended (ID: ${this.currentConversation.id})`);
            console.log(`   Total Duration: ${durationSeconds.toFixed(3)}s`);
            
            this.currentConversation = null;
        }
    }
}

// Initialize ChatBot performance monitor (if chatbot is present)
const chatBotMonitor = new ChatBotPerformanceMonitor();

// Example integration for ChatBot (add this to your chatbot send message function)
function sendChatBotMessage(message) {
    chatBotMonitor.trackMessage(message, true);
    
    // Your existing chatbot logic here
    // ...
    
    // When response is received:
    // chatBotMonitor.trackMessage(response, false);
}

document.addEventListener('DOMContentLoaded', function() {
    console.log(`🚀 LetGodBeTrue Search Page initialized at ${new Date().toISOString()}`);
    
    // Performance tracking for initial page load
    const pageLoadTracker = new PerformanceTracker('Page Load');
    
    const urlParams = new URLSearchParams(window.location.search);
    const queryParam = urlParams.get('q') || urlParams.get('s');
    
    pageLoadTracker.checkpoint('URL Parameters Parsed');
    
    // Don't set any default categories here - let backend decide
    
    const wpSearchQuery = "<?php echo esc_js(get_search_query()); ?>";
    let searchTerm = wpSearchQuery || queryParam || "";
    
    searchTerm = searchTerm.replace(/&quot;/g, '"');
    
    if (searchTerm) {
        searchInput.value = searchTerm;
        
        // Get categories from URL if present, otherwise empty array
        const catParam = urlParams.get('cat');
        let selectedCategories = [];
        
        if (catParam) {
            // Decode categories from URL
            selectedCategories = catParam.split('').map(code => CATEGORY_CODES_REVERSE[code]).filter(Boolean);
            
            // Update checkboxes based on URL
            document.querySelectorAll('input[name="category[]"]').forEach(checkbox => {
                checkbox.checked = selectedCategories.includes(checkbox.value);
            });
        }
        // If no cat param in URL, don't set any checkboxes - let backend decide

        pageLoadTracker.checkpoint('Search Term Populated');
        pageLoadTracker.finish();
        
        // Perform search - backend will handle default categories if needed
        performSearch(searchTerm, selectedCategories);
    } else {
        pageLoadTracker.finish();
    }
});
</script>

<?php get_footer(); ?>