<?php
// Display errors for this test file
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Music Search Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        input, button { padding: 8px; margin-bottom: 10px; }
        #results { margin-top: 20px; }
        .song { display: flex; align-items: center; padding: 10px; border-bottom: 1px solid #eee; cursor: pointer; }
        .song:hover { background-color: #f9f9f9; }
        .song img { width: 50px; height: 50px; margin-right: 10px; }
        .song-info { flex: 1; }
        pre { background: #f5f5f5; padding: 15px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Music Search Test</h1>
    
    <div>
        <input type="text" id="searchInput" placeholder="Search for a song...">
        <button id="searchButton">Search</button>
    </div>
    
    <div id="loading" style="display: none;">Searching...</div>
    <div id="error" style="color: red;"></div>
    
    <div id="results"></div>
    
    <h3>API Response (Debug):</h3>
    <pre id="responseData"></pre>
    
    <script>
        document.getElementById('searchButton').addEventListener('click', searchMusic);
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') searchMusic();
        });
        
        async function searchMusic() {
            const query = document.getElementById('searchInput').value.trim();
            if (!query) return;
            
            const loading = document.getElementById('loading');
            const error = document.getElementById('error');
            const results = document.getElementById('results');
            const responseData = document.getElementById('responseData');
            
            loading.style.display = 'block';
            error.textContent = '';
            results.innerHTML = '';
            responseData.textContent = '';
            
            try {
                const response = await fetch(`/api/search_music.php?q=${encodeURIComponent(query)}`);
                const rawText = await response.text();
                
                // Log the raw response for debugging
                console.log('Raw API Response:', rawText);
                
                try {
                    // Try to parse the response as JSON
                    const data = JSON.parse(rawText);
                    
                    // Display the raw JSON for debugging
                    responseData.textContent = JSON.stringify(data, null, 2);
                    
                    // Check for API error
                    if (data.error) {
                        throw new Error(data.message || data.error);
                    }
                    
                    // Display results
                    if (data.items && data.items.length > 0) {
                        results.innerHTML = data.items.map(item => `
                            <div class="song" data-video-id="${item.id.videoId}">
                                <img src="${item.snippet.thumbnails.default.url}" alt="${item.snippet.title}">
                                <div class="song-info">
                                    <div><strong>${item.snippet.title}</strong></div>
                                    <div>${item.snippet.channelTitle}</div>
                                </div>
                            </div>
                        `).join('');
                        
                        // Add click handlers
                        document.querySelectorAll('.song').forEach(song => {
                            song.addEventListener('click', function() {
                                alert(`Selected: ${this.querySelector('strong').textContent}`);
                            });
                        });
                    } else {
                        results.innerHTML = '<p>No results found</p>';
                    }
                } catch (parseError) {
                    console.error('Parse error:', parseError);
                    error.textContent = `Error parsing response: ${parseError.message}`;
                    responseData.textContent = rawText;
                }
            } catch (fetchError) {
                console.error('Fetch error:', fetchError);
                error.textContent = `Request failed: ${fetchError.message}`;
            } finally {
                loading.style.display = 'none';
            }
        }
    </script>
</body>
</html>
