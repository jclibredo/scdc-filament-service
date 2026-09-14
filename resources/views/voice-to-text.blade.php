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

    <!-- Custom CSS provided -->
    <style>
        body {
            background-color: #0f172a;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: #f8fafc;
        }

        .registration-card {
            max-inline-size: 680px;
            margin: 2rem auto;
            padding: 1.5rem;
            background: #1e293b;
            border-radius: 12px;
            color: #f8fafc;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }

        .form-group {
            margin-block-end: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-block-end: 0.5rem;
            font-weight: 600;
        }

        .form-control {
            inline-size: 100%;
            padding: 0.75rem;
            border-radius: 6px;
            border: 1px solid #334155;
            background: #0f172a;
            color: #fff;
            font-size: 1rem;
        }

        .form-control:focus {
            background: #0f172a;
            color: #fff;
            border-color: #38bdf8;
            box-shadow: 0 0 0 0.25rem rgba(56, 189, 248, 0.25);
        }

        .btn-container {
            display: flex;
            gap: 10px;
            margin-block-start: 1rem;
            flex-wrap: wrap;
        }

        .btn-reg {
            flex: 1;
            padding: 0.75rem;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn-capture {
            background: #0284c7;
            color: white;
        }

        .btn-capture:hover {
            background: #02689c;
            color: white;
        }

        .btn-remove {
            background: #ef4444;
            color: white;
        }

        .btn-remove:hover {
            background: #dc2626;
            color: white;
        }

        .btn-save {
            background: #16a34a;
            color: white;
        }

        .btn-save:hover {
            background: #15803d;
            color: white;
        }

        .btn-warning-custom {
            background: #d97706;
            color: white;
        }

        .btn-warning-custom:hover {
            background: #b45309;
            color: white;
        }

        .btn-info-custom {
            background: #0891b2;
            color: white;
        }

        .btn-info-custom:hover {
            background: #0e7490;
            color: white;
        }

        .btn-reg:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .mic-pulse {
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
            }

            70% {
                box-shadow: 0 0 0 12px rgba(239, 68, 68, 0);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
            }
        }

        .rephrase-option-item {
            transition: all 0.2s ease-in-out;
            background: #0f172a;
            border: 1px solid #334155;
            border-inline-start: 4px solid #38bdf8;
            border-radius: 6px;
            margin-block-end: 8px;
            padding: 12px;
            cursor: pointer;
        }

        .rephrase-option-item:hover {
            background-color: #1e293b;
            transform: translateX(2px);
        }

        /* Centered Smooth Fading Toast */
        #toast-container {
            position: fixed;
            inset-block-start: 50%;
            inset-inline-start: 50%;
            transform: translate(-50%, -50%) scale(0.8);
            inline-size: 300px;
            block-size: 300px;
            border-radius: 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 28px;
            box-sizing: border-box;
            text-align: center;
            z-index: 9999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            backdrop-filter: blur(16px);
            box-shadow: 0 25px 35px -5px rgba(0, 0, 0, 0.4), 0 15px 15px -5px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.18);
            pointer-events: none;
        }

        #toast-container.show {
            opacity: 1;
            visibility: visible;
            transform: translate(-50%, -50%) scale(1);
        }

        #toast-icon {
            inline-size: 88px !important;
            block-size: 88px !important;
            margin-block-end: 16px;
        }

        #toast-message {
            font-size: 1.15rem;
            font-weight: 600;
            line-height: 1.4;
            color: #ffffff;
            word-break: break-word;
        }

        .toast-info {
            background-color: rgba(30, 41, 59, 0.94);
            color: #38bdf8;
        }

        .toast-success {
            background-color: rgba(6, 78, 59, 0.94);
            color: #34d399;
        }

        .toast-error {
            background-color: rgba(127, 29, 29, 0.94);
            color: #f87171;
        }
    </style>
</head>

<body class="py-4">

    <div class="container">
        <div class="registration-card">

            <!-- Card Header & Language Selection Toolbar -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4 pb-3 border-bottom border-secondary">
                <div>
                    <h3 class="mb-1 fw-bold"><i class="bi bi-mic-fill me-2 text-info"></i>Voice to Text Blade</h3>
                    <p class="mb-0 text-muted small text-white-50">Multilingual Voice Engine (English & Tagalog)</p>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <select id="languageSelect" class="form-control form-control-sm bg-dark text-white" style="inline-size: auto;">
                        <option value="en-US">🇬🇧 English (US)</option>
                        <option value="fil-PH">🇵🇭 Tagalog (Pilipino)</option>
                    </select>
                    <span id="speechStatus" class="badge bg-secondary px-3 py-2 rounded-pill font-monospace">READY</span>
                </div>
            </div>

            <!-- Main Control Buttons -->
            <div class="btn-container mb-3">
                <button id="startBtn" class="btn-reg btn-capture">
                    <i class="bi bi-mic-fill"></i> Start Listening
                </button>
                <button id="stopBtn" class="btn-reg btn-remove" disabled>
                    <i class="bi bi-stop-fill"></i> Stop
                </button>
                <button id="clearBtn" class="btn-reg" style="background: #334155; color: white;">
                    <i class="bi bi-trash"></i> Clear
                </button>
            </div>

            <!-- Transcript Input Group -->
            <div class="form-group">
                <label for="transcript">Live Transcript Output</label>
                <textarea id="transcript" class="form-control" rows="5" placeholder="Click 'Start Listening' and speak clearly in your chosen language..."></textarea>
            </div>

            <!-- Secondary Actions Toolbar -->
            <div class="btn-container">
                <button id="copyBtn" class="btn-reg btn-save">
                    <i class="bi bi-clipboard"></i> Copy Text
                </button>
                <button id="analyzeBtn" class="btn-reg btn-warning-custom">
                    <i class="bi bi-spellcheck"></i> Check Grammar
                </button>
                <button id="rephraseBtn" class="btn-reg btn-info-custom">
                    <i class="bi bi-magic"></i> Suggest Rephrases
                </button>
            </div>

            <!-- Grammar Analysis Findings Container -->
            <div id="analysisContainer" class="mt-4 p-3 bg-dark rounded border border-secondary d-none">
                <h6 class="fw-bold text-warning mb-3"><i class="bi bi-exclamation-triangle me-1"></i> Grammar & Spelling Findings</h6>
                <ul id="errorsList" class="list-unstyled mb-0"></ul>
            </div>

            <!-- Rephrase Options Selection Container -->
            <div id="rephraseContainer" class="mt-4 p-3 bg-dark rounded border border-secondary d-none">
                <h6 class="fw-bold text-info mb-3"><i class="bi bi-lightbulb me-1"></i> Rephrased Alternatives (Click to apply instantly)</h6>
                <div id="rephraseList"></div>
            </div>

        </div>
    </div>

    <!-- Centered Toast Notification Container -->
    <div id="toast-container">
        <i id="toast-icon" class="bi bi-info-circle-fill"></i>
        <div id="toast-message">Notification message here</div>
    </div>

    <!-- JavaScript Processing Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

            if (!SpeechRecognition) {
                showToast("Web Speech API is not supported in this browser. Please use Google Chrome or Microsoft Edge.", "error");
                return;
            }

            let recognition = new SpeechRecognition();
            recognition.continuous = true;
            recognition.interimResults = true;

            const languageSelect = document.getElementById('languageSelect');
            recognition.lang = languageSelect.value;

            languageSelect.addEventListener('change', () => {
                recognition.lang = languageSelect.value;
                if (isListening) {
                    recognition.stop();
                    recognition.start();
                }
                showToast(`Language changed to: ${languageSelect.options[languageSelect.selectedIndex].text}`, 'info');
            });

            let isListening = false;
            let transcribedChunks = '';

            const startBtn = document.getElementById('startBtn');
            const stopBtn = document.getElementById('stopBtn');
            const clearBtn = document.getElementById('clearBtn');
            const copyBtn = document.getElementById('copyBtn');
            const analyzeBtn = document.getElementById('analyzeBtn');
            const rephraseBtn = document.getElementById('rephraseBtn');
            const transcriptTextarea = document.getElementById('transcript');
            const speechStatus = document.getElementById('speechStatus');
            const analysisContainer = document.getElementById('analysisContainer');
            const errorsList = document.getElementById('errorsList');
            const rephraseContainer = document.getElementById('rephraseContainer');
            const rephraseList = document.getElementById('rephraseList');

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
                    showToast(`Speech error: ${event.error}`, 'error');
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
                    showToast('Microphone active. Speak your content now...', 'info');
                } catch (err) {
                    console.error("Start failed:", err);
                    stopListeningUI();
                    showToast('Could not start microphone. Check browser permissions.', 'error');
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
            });

            copyBtn.addEventListener('click', () => {
                const text = transcriptTextarea.value.trim();
                if (!text) {
                    showToast('Nothing to copy! Record or input text first.', 'error');
                    return;
                }
                navigator.clipboard.writeText(text).then(() => {
                    showToast('Text copied to clipboard successfully!', 'success');
                });
            });

            // Grammar Analysis with Custom Tagalog Rule Integration
            analyzeBtn.addEventListener('click', async () => {
                const text = transcriptTextarea.value.trim();
                if (!text) {
                    showToast('Please provide text to analyze first.', 'error');
                    return;
                }

                rephraseContainer.classList.add('d-none');
                analyzeBtn.disabled = true;
                analyzeBtn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Checking...`;

                try {
                    const isTagalog = languageSelect.value.startsWith('fil');
                    const activeLangCode = isTagalog ? 'tl' : 'en-US';

                    const response = await fetch('https://api.languagetool.org/v2/check', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: new URLSearchParams({
                            'text': text,
                            'language': activeLangCode
                        })
                    });
                    const data = await response.json();

                    let matches = data.matches || [];

                    if (isTagalog) {
                        const customTagalogMatches = checkTagalogGrammarRules(text);
                        matches = [...customTagalogMatches, ...matches];
                    }

                    displayAnalysis(matches);
                } catch (error) {
                    showToast('Failed to connect to grammar service.', 'error');
                } finally {
                    analyzeBtn.disabled = false;
                    analyzeBtn.innerHTML = `<i class="bi bi-spellcheck"></i> Check Grammar`;
                }
            });

            // Custom local Tagalog rule engine for common particle corrections (ng vs nang, kung vs kong)
            function checkTagalogGrammarRules(text) {
                let matches = [];
                const nangBeforeNounRegex = /\bnang\s+(bahay|kotse|tao|pagkain|tubig|pera|lugar|oras|araw|guro|estudyante)\b/gi;
                let match;
                while ((match = nangBeforeNounRegex.exec(text)) !== null) {
                    matches.push({
                        offset: match.index,
                        length: 4,
                        message: 'Maaaring "ng" ang dapat gamitin dito kaysa sa "nang" dahil sinusundan ito ng pangngalan (noun).',
                        replacements: [{
                            value: 'ng'
                        }]
                    });
                }

                const kongVsKungRegex = /\bkong\s+(gusto|alam|makita|pumunta|malaman)\b/gi;
                while ((match = kongVsKungRegex.exec(text)) !== null) {
                    matches.push({
                        offset: match.index,
                        length: 4,
                        message: 'Maaaring "kung" angkop dito (conditional statement).',
                        replacements: [{
                            value: 'kung'
                        }]
                    });
                }

                const ngBeforeAdverbRegex = /\bng\s+(mabilis|mabagal|maayos|mabuti|tuluyan|bigla|paunti-unti)\b/gi;
                while ((match = ngBeforeAdverbRegex.exec(text)) !== null) {
                    matches.push({
                        offset: match.index,
                        length: 2,
                        message: 'Maaaring "nang" ang dapat gamitin dito dahil sumasagot ito sa tanong na "paano".',
                        replacements: [{
                            value: 'nang'
                        }]
                    });
                }

                return matches;
            }

            rephraseBtn.addEventListener('click', () => {
                const text = transcriptTextarea.value.trim();
                if (!text) {
                    showToast('Please provide text to rephrase first.', 'error');
                    return;
                }

                analysisContainer.classList.add('d-none');
                rephraseList.innerHTML = '';
                rephraseContainer.classList.remove('d-none');

                const isTagalog = languageSelect.value.startsWith('fil');
                const suggestions = generateRephrases(text, isTagalog);

                suggestions.forEach((option) => {
                    const div = document.createElement('div');
                    div.className = 'rephrase-option-item';
                    div.innerHTML = `
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-info text-dark mb-1">${option.style}</span>
                                <p class="mb-0 text-white fw-medium">${option.text}</p>
                            </div>
                            <button class="btn btn-sm btn-success text-nowrap rounded-pill px-3 ms-2">
                                <i class="bi bi-check2 me-1"></i> Use
                            </button>
                        </div>
                    `;

                    const applyOption = () => {
                        transcriptTextarea.value = option.text;
                        transcribedChunks = option.text;
                        rephraseContainer.classList.add('d-none');
                        showToast(`Applied "${option.style}" option successfully!`, 'success');
                    };

                    div.addEventListener('click', applyOption);
                    rephraseList.appendChild(div);
                });

                showToast('Select a preferred rephrased sentence alternative below:', 'info');
            });

            // Duplicate Word Cleanup Filter
            function removeDuplicateWords(str) {
                return str.replace(/\b([a-zA-ZñÑáéíóúÁÉÍÓÚ]+)(\s+\1\b)+/gi, '$1');
            }

            function generateRephrases(input, isTagalog) {
                let clean = input.replace(/[.!?]+$/, '').trim();

                let rawOptions = [];
                if (isTagalog) {
                    rawOptions = [{
                            style: 'Pormal / Professional (Tagalog)',
                            text: 'Nais ko pong iparating na ' + clean.charAt(0).toLowerCase() + clean.slice(1) + '.'
                        },
                        {
                            style: 'Malinaw at Direkta (Clear)',
                            text: capitalizeFirstLetter(clean) + '.'
                        },
                        {
                            style: 'Magalang / Polite (Tagalog)',
                            text: 'Maaari po bang sabihin na ' + clean.charAt(0).toLowerCase() + clean.slice(1) + '?'
                        }
                    ];
                } else {
                    rawOptions = [{
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

                return rawOptions.map(opt => ({
                    style: opt.style,
                    text: removeDuplicateWords(opt.text)
                }));
            }

            function capitalizeFirstLetter(string) {
                return string.charAt(0).toUpperCase() + string.slice(1);
            }

            function displayAnalysis(matches) {
                errorsList.innerHTML = '';
                analysisContainer.classList.remove('d-none');

                if (matches.length === 0) {
                    errorsList.innerHTML = `<li class="text-success"><i class="bi bi-check-circle-fill me-2"></i> No spelling or grammar errors detected!</li>`;
                    return;
                }

                matches.forEach(match => {
                    const errorWord = transcriptTextarea.value.substr(match.offset, match.length);
                    let suggestionButtons = match.replacements && match.replacements.length > 0 ?
                        match.replacements.slice(0, 4).map(r => `<button class="btn btn-sm btn-outline-success me-1 mb-1 rounded-pill apply-fix-btn" data-offset="${match.offset}" data-length="${match.length}" data-replacement="${r.value}">${r.value}</button>`).join('') :
                        `<span class="text-muted small">No direct replacements available</span>`;

                    const li = document.createElement('li');
                    li.className = 'mb-3 pb-2 border-bottom border-secondary';
                    li.innerHTML = `
                        <div>
                            <span class="badge bg-danger mb-1">Issue Found</span> 
                            <span class="fw-bold text-danger me-2">${errorWord}</span>
                            <p class="mb-2 text-white-50 small">${match.message}</p>
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
                        showToast(`Applied fix: "${replacement}".`, 'success');
                        e.target.closest('li').remove();
                        if (errorsList.children.length === 0) analysisContainer.classList.add('d-none');
                    });
                });
            }

            // Toast Notification Handler using your reference styles
            function showToast(message, type = 'info') {
                const container = document.getElementById('toast-container');
                const msgEl = document.getElementById('toast-message');
                const iconEl = document.getElementById('toast-icon');

                container.className = '';
                if (type === 'success') {
                    container.classList.add('toast-success');
                    iconEl.className = 'bi bi-check-circle-fill';
                } else if (type === 'error') {
                    container.classList.add('toast-error');
                    iconEl.className = 'bi bi-exclamation-octagon-fill';
                } else {
                    container.classList.add('toast-info');
                    iconEl.className = 'bi bi-info-circle-fill';
                }

                msgEl.innerText = message;
                container.classList.add('show');

                setTimeout(() => {
                    container.classList.remove('show');
                }, 3000);
            }
        });
    </script>
</body>

</html>