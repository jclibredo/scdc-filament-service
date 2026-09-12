<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voice to Text Converter & Grammar Checker</title>

    <!-- Local Bootstrap 5 CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- Local Bootstrap Icons -->
    <link href="css/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f4f6f9;
        }

        .mic-pulse {
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
            }

            70% {
                box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
            }
        }
    </style>
</head>

<body class="py-5">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="card shadow border-0 rounded-4">
                    <div class="card-header bg-primary text-white p-4 d-flex justify-content-between align-items-center rounded-top-4">
                        <h4 class="mb-0 fw-bold"><i class="bi bi-mic"></i> Live Voice to Text Converter</h4>
                        <span id="speechStatus" class="badge bg-light text-dark px-3 py-2 rounded-pill">Ready</span>
                    </div>

                    <div class="card-body p-4">

                        <!-- Controls -->
                        <div class="d-flex flex-wrap gap-2 mb-3 align-items-center">
                            <button id="startBtn" class="btn btn-danger px-4">
                                <i class="bi bi-record-fill"></i> Start Listening
                            </button>
                            <button id="stopBtn" class="btn btn-secondary px-4" disabled>
                                <i class="bi bi-stop-fill"></i> Stop
                            </button>
                            <button id="clearBtn" class="btn btn-outline-danger ms-auto">
                                <i class="bi bi-trash"></i> Clear Text
                            </button>
                        </div>

                        <!-- Transcript Textarea -->
                        <div class="mb-3">
                            <textarea id="transcript" class="form-control fs-5 p-3 rounded-3" rows="8" placeholder="Click 'Start Listening' and speak into your microphone..."></textarea>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button id="copyBtn" class="btn btn-success px-4">
                                <i class="bi bi-clipboard"></i> Copy Text
                            </button>
                            <button id="analyzeBtn" class="btn btn-warning px-4">
                                <i class="bi bi-spellcheck"></i> Check Spelling & Grammar
                            </button>
                        </div>

                        <!-- Alerts / Feedback -->
                        <div id="alertBox" class="alert alert-info d-none mt-3" role="alert"></div>

                        <!-- Analysis / Misspelled Words Container -->
                        <div id="analysisContainer" class="card mt-4 border-0 bg-light rounded-3 d-none">
                            <div class="card-header bg-secondary text-white fw-bold">
                                <i class="bi bi-search"></i> Analysis Results & Fix Suggestions
                            </div>
                            <div class="card-body p-0">
                                <ul id="errorsList" class="list-group list-group-flush rounded-bottom-3"></ul>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

            if (!SpeechRecognition) {
                alert("Your browser does not support Web Speech API. Please open this page in Google Chrome, Microsoft Edge, or Safari.");
                return;
            }

            let recognition = new SpeechRecognition();
            recognition.continuous = true;
            recognition.interimResults = true; // Essential for real-time instant typing
            recognition.lang = 'en-US';

            let isListening = false;
            let transcribedChunks = '';

            // Element References
            const startBtn = document.getElementById('startBtn');
            const stopBtn = document.getElementById('stopBtn');
            const clearBtn = document.getElementById('clearBtn');
            const copyBtn = document.getElementById('copyBtn');
            const analyzeBtn = document.getElementById('analyzeBtn');
            const transcriptTextarea = document.getElementById('transcript');
            const speechStatus = document.getElementById('speechStatus');
            const alertBox = document.getElementById('alertBox');
            const analysisContainer = document.getElementById('analysisContainer');
            const errorsList = document.getElementById('errorsList');

            // Instant translation engine stream
            recognition.onresult = (event) => {
                let interim = '';
                let final = '';

                for (let i = event.resultIndex; i < event.results.length; ++i) {
                    if (event.results[i].isFinal) {
                        final += event.results[i][0].transcript;
                    } else {
                        interim += event.results[i][0].transcript;
                    }
                }

                if (final) {
                    transcribedChunks += (transcribedChunks ? ' ' : '') + final.trim();
                }

                // Immediately display text combining persistent chunks and fast streaming interim text
                transcriptTextarea.value = transcribedChunks + (interim ? ' ' + interim : '');
            };

            recognition.onerror = (event) => {
                console.error('Speech recognition error:', event.error);
                if (event.error !== 'aborted') {
                    showAlert(`Speech error: ${event.error}`, 'danger');
                }
                stopListeningUI();
            };

            recognition.onend = () => {
                if (isListening) {
                    try {
                        recognition.start(); // Seamless continuity check
                    } catch (e) {
                        stopListeningUI();
                    }
                } else {
                    stopListeningUI();
                }
            };

            // Start Button
            startBtn.addEventListener('click', () => {
                if (isListening) return;

                transcribedChunks = transcriptTextarea.value.trim();

                try {
                    recognition.start();
                    isListening = true;
                    startBtn.disabled = true;
                    startBtn.classList.add('mic-pulse');
                    stopBtn.disabled = false;
                    speechStatus.className = 'badge bg-danger px-3 py-2 rounded-pill';
                    speechStatus.innerText = 'Listening...';
                    showAlert('Microphone active. Start speaking...', 'info');
                } catch (err) {
                    console.error("Start failed:", err);
                    stopListeningUI();
                    showAlert('Could not start microphone. Check browser permissions.', 'danger');
                }
            });

            // Stop Button
            stopBtn.addEventListener('click', () => {
                isListening = false;
                try {
                    recognition.stop();
                } catch (e) {}
                stopListeningUI();
            });

            function stopListeningUI() {
                isListening = false;
                startBtn.disabled = false;
                startBtn.classList.remove('mic-pulse');
                stopBtn.disabled = true;
                speechStatus.className = 'badge bg-light text-dark px-3 py-2 rounded-pill';
                speechStatus.innerText = 'Stopped';
            }

            // Clear Button
            clearBtn.addEventListener('click', () => {
                transcribedChunks = '';
                transcriptTextarea.value = '';
                analysisContainer.classList.add('d-none');
                alertBox.classList.add('d-none');
            });

            // Copy Button
            copyBtn.addEventListener('click', () => {
                const text = transcriptTextarea.value.trim();
                if (!text) {
                    showAlert('Nothing to copy! Convert some speech first.', 'warning');
                    return;
                }

                navigator.clipboard.writeText(text).then(() => {
                    showAlert('Text copied to clipboard successfully!', 'success');
                }).catch(() => {
                    showAlert('Failed to copy text automatically.', 'danger');
                });
            });

            // Check Spelling & Grammar (LanguageTool Public API)
            analyzeBtn.addEventListener('click', async () => {
                const text = transcriptTextarea.value.trim();
                if (!text) {
                    showAlert('Please speak or enter text before analyzing.', 'warning');
                    return;
                }

                analyzeBtn.disabled = true;
                analyzeBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Checking...`;

                try {
                    const response = await fetch('https://api.languagetool.org/v2/check', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            'text': text,
                            'language': 'en-US'
                        })
                    });

                    const data = await response.json();
                    displayAnalysis(data.matches);

                } catch (error) {
                    console.error(error);
                    showAlert('Failed to perform grammar check. Please check your network connection.', 'danger');
                } finally {
                    analyzeBtn.disabled = false;
                    analyzeBtn.innerHTML = `<i class="bi bi-spellcheck"></i> Check Spelling & Grammar`;
                }
            });

            // Render Misspelled Words & Clickable Suggestion Actions
            function displayAnalysis(matches) {
                errorsList.innerHTML = '';
                analysisContainer.classList.remove('d-none');

                if (matches.length === 0) {
                    errorsList.innerHTML = `<li class="list-group-item text-success p-3"><i class="bi bi-check-circle-fill me-2"></i> No spelling or grammar errors detected!</li>`;
                    return;
                }

                matches.forEach(match => {
                    const errorWord = transcriptTextarea.value.substr(match.offset, match.length);

                    let suggestionButtons = '';
                    if (match.replacements && match.replacements.length > 0) {
                        suggestionButtons = match.replacements.slice(0, 4).map(r =>
                            `<button class="btn btn-sm btn-outline-success me-1 mb-1 apply-fix-btn" data-offset="${match.offset}" data-length="${match.length}" data-replacement="${r.value}">${r.value}</button>`
                        ).join('');
                    } else {
                        suggestionButtons = `<span class="text-muted small">No direct replacements available</span>`;
                    }

                    const li = document.createElement('li');
                    li.className = 'list-group-item p-3';
                    li.innerHTML = `
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="badge bg-danger mb-2">Issue</span> 
                                <span class="fw-bold text-danger fs-6 me-2">${errorWord}</span>
                                <p class="mb-2 text-secondary">${match.message}</p>
                                <div><small class="text-muted d-block mb-1">Click a suggestion to replace it:</small>${suggestionButtons}</div>
                            </div>
                        </div>
                    `;
                    errorsList.appendChild(li);
                });

                document.querySelectorAll('.apply-fix-btn').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const offset = parseInt(e.target.getAttribute('data-offset'));
                        const length = parseInt(e.target.getAttribute('data-length'));
                        const replacement = e.target.getAttribute('data-replacement');

                        const currentText = transcriptTextarea.value;
                        const updatedText = currentText.substring(0, offset) + replacement + currentText.substring(offset + length);

                        transcriptTextarea.value = updatedText;
                        transcribedChunks = updatedText;

                        showAlert(`Applied fix: "${replacement}".`, 'success');

                        e.target.closest('li').remove();
                        if (errorsList.children.length === 0) {
                            analysisContainer.classList.add('d-none');
                        }
                    });
                });
            }

            function showAlert(message, type) {
                alertBox.className = `alert alert-${type} mt-3`;
                alertBox.innerText = message;
                alertBox.classList.remove('d-none');
            }
        });
    </script>

</body>

</html>