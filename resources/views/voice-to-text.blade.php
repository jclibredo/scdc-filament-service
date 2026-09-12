<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voice to Text Converter & AI Rephraser</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8fafc;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
        }

        /* Face-Verify Card Layout Inspiration */
        .verification-card {
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            border-radius: 1rem;
            background: #ffffff;
            overflow: hidden;
        }

        .card-header-custom {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #ffffff;
            padding: 1.5rem;
        }

        .mic-pulse {
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
            }

            70% {
                box-shadow: 0 0 0 12px rgba(220, 53, 69, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
            }
        }

        .rephrase-option-item {
            transition: all 0.2s ease-in-out;
            border-left: 4px solid #0dcaf0;
        }

        .rephrase-option-item:hover {
            background-color: #f1f5f9;
            transform: translateX(2px);
        }
    </style>
</head>

<body class="py-5">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9 col-md-11">

                <!-- Main Layout Card (Face-Verify Inspired Structure) -->
                <div class="verification-card">

                    <!-- Header Section -->
                    <div class="card-header-custom d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1 fw-bold"><i class="bi bi-shield-check me-2"></i>Smart Speech & Rephrase Studio</h4>
                            <p class="mb-0 text-muted small text-white-50">Secure voice transcription engine with live grammar & style alignment</p>
                        </div>
                        <span id="speechStatus" class="badge bg-secondary px-3 py-2 rounded-pill font-monospace">Ready</span>
                    </div>

                    <div class="card-body p-4 p-md-5">

                        <!-- Control Action Toolbar -->
                        <div class="d-flex flex-wrap gap-2 mb-4 align-items-center">
                            <button id="startBtn" class="btn btn-danger px-4 rounded-pill">
                                <i class="bi bi-mic-fill me-1"></i> Start Listening
                            </button>
                            <button id="stopBtn" class="btn btn-outline-secondary px-4 rounded-pill" disabled>
                                <i class="bi bi-stop-fill me-1"></i> Stop
                            </button>
                            <button id="clearBtn" class="btn btn-outline-danger ms-auto rounded-pill">
                                <i class="bi bi-trash me-1"></i> Clear Workspace
                            </button>
                        </div>

                        <!-- Transcript Input Area -->
                        <div class="mb-4">
                            <label for="transcript" class="form-label fw-semibold text-secondary small text-uppercase tracking-wider">Live Transcript Output</label>
                            <textarea id="transcript" class="form-control fs-5 p-3 rounded-3 border-2" rows="5" placeholder="Click 'Start Listening' and speak clearly..."></textarea>
                        </div>

                        <!-- Secondary Actions: Copy, Grammar, Rephrase -->
                        <div class="d-flex flex-wrap gap-2 mb-4">
                            <button id="copyBtn" class="btn btn-success px-3 rounded-pill">
                                <i class="bi bi-clipboard me-1"></i> Copy Text
                            </button>
                            <button id="analyzeBtn" class="btn btn-warning text-dark px-3 rounded-pill fw-medium">
                                <i class="bi bi-spellcheck me-1"></i> Check Grammar
                            </button>
                            <button id="rephraseBtn" class="btn btn-info text-white px-3 rounded-pill fw-medium">
                                <i class="bi bi-magic me-1"></i> Suggest Rephrases
                            </button>
                        </div>

                        <!-- Notification Alert Box -->
                        <div id="alertBox" class="alert alert-info d-none mt-3 rounded-3" role="alert"></div>

                        <!-- Grammar Error Findings Container -->
                        <div id="analysisContainer" class="card mt-4 border-0 bg-light rounded-3 d-none">
                            <div class="card-header bg-secondary text-white fw-bold rounded-top-3">
                                <i class="bi bi-exclamation-triangle me-1"></i> Grammar & Spelling Analysis
                            </div>
                            <div class="card-body p-0">
                                <ul id="errorsList" class="list-group list-group-flush rounded-bottom-3"></ul>
                            </div>
                        </div>

                        <!-- Rephrase Options Selection Container -->
                        <div id="rephraseContainer" class="card mt-4 border-0 bg-light rounded-3 d-none">
                            <div class="card-header bg-info text-white fw-bold rounded-top-3">
                                <i class="bi bi-lightbulb me-1"></i> Choose a Rephrased Sentence Option (Clicking replaces text instantly)
                            </div>
                            <div class="card-body p-0">
                                <ul id="rephraseList" class="list-group list-group-flush rounded-bottom-3"></ul>
                            </div>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- JavaScript Processing Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

            if (!SpeechRecognition) {
                alert("Web Speech API is not supported in this browser. Please use Google Chrome or Microsoft Edge.");
                return;
            }

            let recognition = new SpeechRecognition();
            recognition.continuous = true;
            recognition.interimResults = true;
            recognition.lang = 'en-US';

            let isListening = false;
            let transcribedChunks = '';

            // DOM Element References
            const startBtn = document.getElementById('startBtn');
            const stopBtn = document.getElementById('stopBtn');
            const clearBtn = document.getElementById('clearBtn');
            const copyBtn = document.getElementById('copyBtn');
            const analyzeBtn = document.getElementById('analyzeBtn');
            const rephraseBtn = document.getElementById('rephraseBtn');
            const transcriptTextarea = document.getElementById('transcript');
            const speechStatus = document.getElementById('speechStatus');
            const alertBox = document.getElementById('alertBox');
            const analysisContainer = document.getElementById('analysisContainer');
            const errorsList = document.getElementById('errorsList');
            const rephraseContainer = document.getElementById('rephraseContainer');
            const rephraseList = document.getElementById('rephraseList');

            // Real-time voice stream ingestion
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
                        recognition.start();
                    } catch (e) {
                        stopListeningUI();
                    }
                } else {
                    stopListeningUI();
                }
            };

            startBtn.addEventListener('click', () => {
                if (isListening) return;
                transcribedChunks = transcriptTextarea.value.trim();

                try {
                    recognition.start();
                    isListening = true;
                    startBtn.disabled = true;
                    startBtn.classList.add('mic-pulse');
                    stopBtn.disabled = false;
                    speechStatus.className = 'badge bg-danger px-3 py-2 rounded-pill font-monospace';
                    speechStatus.innerText = 'LISTENING';
                    showAlert('Microphone active. Speak your content now...', 'info');
                } catch (err) {
                    console.error("Start failed:", err);
                    stopListeningUI();
                    showAlert('Could not start microphone. Check browser permissions.', 'danger');
                }
            });

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
                speechStatus.className = 'badge bg-secondary px-3 py-2 rounded-pill font-monospace';
                speechStatus.innerText = 'READY';
            }

            clearBtn.addEventListener('click', () => {
                transcribedChunks = '';
                transcriptTextarea.value = '';
                analysisContainer.classList.add('d-none');
                rephraseContainer.classList.add('d-none');
                alertBox.classList.add('d-none');
            });

            copyBtn.addEventListener('click', () => {
                const text = transcriptTextarea.value.trim();
                if (!text) {
                    showAlert('Nothing to copy! Record or input text first.', 'warning');
                    return;
                }
                navigator.clipboard.writeText(text).then(() => {
                    showAlert('Text copied to clipboard successfully!', 'success');
                });
            });

            // Grammar Analysis via LanguageTool API
            analyzeBtn.addEventListener('click', async () => {
                const text = transcriptTextarea.value.trim();
                if (!text) {
                    showAlert('Please provide text to analyze first.', 'warning');
                    return;
                }

                rephraseContainer.classList.add('d-none');
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
                    showAlert('Failed to connect to grammar service.', 'danger');
                } finally {
                    analyzeBtn.disabled = false;
                    analyzeBtn.innerHTML = `<i class="bi bi-spellcheck me-1"></i> Check Grammar`;
                }
            });

            // Rephrase Variations Generator
            rephraseBtn.addEventListener('click', () => {
                const text = transcriptTextarea.value.trim();
                if (!text) {
                    showAlert('Please provide text to rephrase first.', 'warning');
                    return;
                }

                analysisContainer.classList.add('d-none');
                rephraseList.innerHTML = '';
                rephraseContainer.classList.remove('d-none');

                const suggestions = generateRephrases(text);

                suggestions.forEach((option) => {
                    const li = document.createElement('li');
                    li.className = 'list-group-item p-3 rephrase-option-item d-flex justify-content-between align-items-center';
                    li.style.cursor = 'pointer';
                    li.innerHTML = `
                        <div class="me-3">
                            <span class="badge bg-info text-dark mb-1">${option.style}</span>
                            <p class="mb-0 text-dark fw-medium">${option.text}</p>
                        </div>
                        <button class="btn btn-sm btn-primary text-nowrap rounded-pill px-3 shadow-sm select-rephrase-btn">
                            <i class="bi bi-check2 me-1"></i> Use Option
                        </button>
                    `;

                    // Function to execute text replacement inside textarea immediately
                    const applyOption = () => {
                        transcriptTextarea.value = option.text;
                        transcribedChunks = option.text;
                        rephraseContainer.classList.add('d-none');
                        showAlert(`Applied "${option.style}" option successfully!`, 'success');
                    };

                    li.querySelector('.select-rephrase-btn').addEventListener('click', (e) => {
                        e.stopPropagation();
                        applyOption();
                    });

                    li.addEventListener('click', applyOption);
                    rephraseList.appendChild(li);
                });

                showAlert('Select a preferred rephrased sentence alternative below:', 'info');
            });

            function generateRephrases(input) {
                let clean = input.replace(/[.!?]+$/, '').trim();
                return [{
                        style: 'Professional & Corporate',
                        text: capitalizeFirstLetter(clean.replace(/\b(gonna|wanna|gotta|yeah)\b/gi, m => m.toLowerCase() === 'gonna' ? 'going to' : 'want to')) + '.'
                    },
                    {
                        style: 'Clear & Direct',
                        text: capitalizeFirstLetter(clean) + '.'
                    },
                    {
                        style: 'Polite / Formal Inquiry',
                        text: 'I would like to note that ' + clean.charAt(0).toLowerCase() + clean.slice(1) + '.'
                    },
                    {
                        style: 'Concise Summary',
                        text: 'Briefly: ' + clean.charAt(0).toLowerCase() + clean.slice(1) + '.'
                    }
                ];
            }

            function capitalizeFirstLetter(string) {
                return string.charAt(0).toUpperCase() + string.slice(1);
            }

            function displayAnalysis(matches) {
                errorsList.innerHTML = '';
                analysisContainer.classList.remove('d-none');

                if (matches.length === 0) {
                    errorsList.innerHTML = `<li class="list-group-item text-success p-3"><i class="bi bi-check-circle-fill me-2"></i> No spelling or grammar errors detected!</li>`;
                    return;
                }

                matches.forEach(match => {
                    const errorWord = transcriptTextarea.value.substr(match.offset, match.length);
                    let suggestionButtons = match.replacements && match.replacements.length > 0 ?
                        match.replacements.slice(0, 4).map(r => `<button class="btn btn-sm btn-outline-success me-1 mb-1 rounded-pill apply-fix-btn" data-offset="${match.offset}" data-length="${match.length}" data-replacement="${r.value}">${r.value}</button>`).join('') :
                        `<span class="text-muted small">No direct replacements available</span>`;

                    const li = document.createElement('li');
                    li.className = 'list-group-item p-3';
                    li.innerHTML = `
                        <div>
                            <span class="badge bg-danger mb-2">Issue Found</span> 
                            <span class="fw-bold text-danger fs-6 me-2">${errorWord}</span>
                            <p class="mb-2 text-secondary small">${match.message}</p>
                            <div><small class="text-muted d-block mb-1">Quick fixes:</small>${suggestionButtons}</div>
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
                        if (errorsList.children.length === 0) analysisContainer.classList.add('d-none');
                    });
                });
            }

            function showAlert(message, type) {
                alertBox.className = `alert alert-${type} mt-3 rounded-3`;
                alertBox.innerText = message;
                alertBox.classList.remove('d-none');
            }
        });
    </script>
</body>

</html>