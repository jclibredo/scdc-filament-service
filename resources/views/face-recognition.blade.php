<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SURGE Face Recognition</title>

    <script src="{{ asset('js/face-api.js') }}"></script>
    <!-- Lucide Icons CDN -->
    <script src="{{ asset('js/lucide.min.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('css/face-recognition.css') }}">
</head>

<body>
    <input type="hidden" id="employeeId" value="{{ $employee->id ?? $employeeId ?? '' }}">
    <input type="hidden" id="activeEmployeeId" value="">
    <!-- Main Kiosk Frame -->
    <div class="kiosk-card">
        <header>
            <h2>SURGE Identification</h2>
            <p>Real-Time Facial Identification</p>
        </header>

        <!-- Camera Container with Embedded HUD & Floating Center Notification -->
        <div id="video-container">
            <div class="hud-status-bar">
                <div id="status" class="hud-badge">
                    <i data-lucide="loader-2"></i> Initializing...
                </div>
            </div>

            <video id="webcam" width="640" height="480" autoplay muted playsinline></video>
            <canvas id="overlay" width="640" height="480"></canvas>

            <!-- Center Floating Toast Message Overlay -->
            <div id="toast-overlay" class="center-float-toast">
                <div class="toast-icon-wrapper">
                    <i id="toast-icon" data-lucide="check"></i>
                </div>
                <div id="toast-title" class="toast-title">Success</div>
                <div id="toast-time" class="toast-time">
                    <i data-lucide="clock"></i> <span id="toast-time-val">--:--:--</span>
                </div>
                <div id="toast-msg" class="toast-message">Action processed successfully.</div>
            </div>

            <div id="position-guide" class="hud-guide">
                <i data-lucide="camera"></i> Waiting for camera...
            </div>
        </div>

        <!-- Verification Result Status -->
        <div class="status-panel">
            <h3 id="access-status">
                <i data-lucide="clock"></i> WAITING
            </h3>
            <p id="result" class="identity-text">Identity: Waiting...</p>
        </div>

        <!-- Attendance Action Buttons -->
        <div class="attendance-grid">
            <button class="btn-action btn-time-in" onclick="logAttendance('time_in')">TIME IN</button>
            <button class="btn-action btn-break-out" onclick="logAttendance('break_out')">BREAK OUT</button>
            <button class="btn-action btn-break-in" onclick="logAttendance('break_in')">BREAK IN</button>
            <button class="btn-action btn-time-out" onclick="logAttendance('time_out')">TIME OUT</button>
        </div>

        <!-- Register Box -->
        <div id="register-container">
            <p style="font-size: 0.85rem; color: var(--text-main);">Unregistered Face: <strong>{{ $employee->lastname ?? '' }}, {{ $employee->firstname ?? '' }}</strong></p>
            <button class="btn-register" onclick="registerFace()">
                Capture & Register Face
            </button>
        </div>
    </div>

    <script>
        let currentDescriptor = null;
        let isProcessing = false;
        let isEyeClosed = false;
        let hasBlinked = false;
        let toastTimeout = null;

        const MIN_FACE_WIDTH = 180;
        const MAX_FACE_WIDTH = 340;

        function getCurrentTimeString() {
            const now = new Date();
            return now.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });
        }

        function showCenterToast(type, title, message) {
            const toast = document.getElementById('toast-overlay');
            const icon = document.getElementById('toast-icon');
            const toastTitle = document.getElementById('toast-title');
            const toastTimeVal = document.getElementById('toast-time-val');
            const toastMsg = document.getElementById('toast-msg');

            clearTimeout(toastTimeout);

            toast.className = 'center-float-toast';

            if (type === 'success') {
                toast.classList.add('toast-success');
                icon.setAttribute('data-lucide', 'check-circle-2');
            } else {
                toast.classList.add('toast-error');
                icon.setAttribute('data-lucide', 'x-circle');
            }

            toastTitle.innerText = title;
            toastTimeVal.innerText = getCurrentTimeString();
            toastMsg.innerText = message;

            lucide.createIcons();

            setTimeout(() => {
                toast.classList.add('show');
            }, 10);

            toastTimeout = setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        function getEuclideanDistance(pt1, pt2) {
            return Math.sqrt(Math.pow(pt1.x - pt2.x, 2) + Math.pow(pt1.y - pt2.y, 2));
        }

        function calculateEAR(eyeLandmarks) {
            const v1 = getEuclideanDistance(eyeLandmarks[1], eyeLandmarks[5]);
            const v2 = getEuclideanDistance(eyeLandmarks[2], eyeLandmarks[4]);
            const h = getEuclideanDistance(eyeLandmarks[0], eyeLandmarks[3]);

            return (v1 + v2) / (2.0 * h);
        }

        function trackBlinks(landmarks) {
            const leftEAR = calculateEAR(landmarks.getLeftEye());
            const rightEAR = calculateEAR(landmarks.getRightEye());
            const avgEAR = (leftEAR + rightEAR) / 2.0;

            const BLINK_THRESHOLD_CLOSED = 0.25;
            const BLINK_THRESHOLD_OPEN = 0.27;

            if (avgEAR < BLINK_THRESHOLD_CLOSED) {
                isEyeClosed = true;
            } else if (avgEAR > BLINK_THRESHOLD_OPEN && isEyeClosed) {
                hasBlinked = true;
                isEyeClosed = false;
            }

            return {
                hasBlinked,
                avgEAR
            };
        }

        function setActionButtonsState(enabled) {
            const buttons = document.querySelectorAll('.btn-action');
            buttons.forEach(btn => {
                if (enabled) {
                    btn.classList.add('active');
                } else {
                    btn.classList.remove('active');
                }
            });
        }

        async function logAttendance(type) {
            const employeeId = document.getElementById('activeEmployeeId').value;
            const accessStatusText = document.getElementById('access-status');
            const resultText = document.getElementById('result');
            const readableAction = type.replace('_', ' ').toUpperCase();

            if (!employeeId) {
                accessStatusText.innerHTML = `<i data-lucide="alert-circle"></i> NO USER`;
                accessStatusText.className = "access-denied";
                resultText.innerText = "No identified employee associated with this action.";
                showCenterToast('error', 'FAILED', 'No active identified employee', null);
                lucide.createIcons();
                return;
            }

            // Format local time as YYYY-MM-DD HH:mm:ss for backend database saving
            const now = new Date();
            const localTimestamp = now.getFullYear() + '-' +
                String(now.getMonth() + 1).padStart(2, '0') + '-' +
                String(now.getDate()).padStart(2, '0') + ' ' +
                String(now.getHours()).padStart(2, '0') + ':' +
                String(now.getMinutes()).padStart(2, '0') + ':' +
                String(now.getSeconds()).padStart(2, '0');

            accessStatusText.innerHTML = `<i data-lucide="loader-2"></i> RECORDING ${readableAction}...`;
            accessStatusText.className = "";
            lucide.createIcons();

            try {
                const response = await fetch('/attendance-log', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        employee_id: employeeId,
                        type: type,
                        logged_at: localTimestamp // Pass local timestamp
                    })
                });

                const data = await response.json();

                if (response.ok) {
                    accessStatusText.innerHTML = `<i data-lucide="check-circle-2"></i> ${readableAction} SUCCESS`;
                    accessStatusText.className = "access-granted";
                    resultText.innerText = data.message || `Recorded ${readableAction} for Employee ID #${employeeId}`;

                    showCenterToast('success', `${readableAction} RECORDED`, data.message || `Entry logged successfully`, employeeId);
                } else {
                    accessStatusText.innerHTML = `<i data-lucide="alert-triangle"></i> LOG FAILED`;
                    accessStatusText.className = "access-denied";
                    resultText.innerText = data.message || "Failed to log attendance entry.";

                    showCenterToast('error', 'ACTION FAILED', data.message || "Could not log entry", employeeId);
                }
            } catch (error) {
                console.error("Attendance logging error:", error);
                accessStatusText.innerHTML = `<i data-lucide="x-circle"></i> SERVER ERROR`;
                accessStatusText.className = "access-denied";
                resultText.innerText = "Error connecting to attendance service.";

                showCenterToast('error', 'SERVER ERROR', 'Failed to connect to the server', employeeId);
            } finally {
                lucide.createIcons();
            }
        }

        async function registerFace() {
            const employeeId = document.getElementById('employeeId').value;

            if (!currentDescriptor) {
                showCenterToast('error', 'NO FACE DETECTED', 'Position face clearly to register');
                return;
            }

            if (!employeeId) {
                showCenterToast('error', 'MISSING ID', 'No target employee ID specified');
                return;
            }

            try {
                const response = await fetch('/face-register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        employee_id: employeeId,
                        face_descriptor: currentDescriptor
                    })
                });

                const data = await response.json();

                if (response.ok) {
                    showCenterToast('success', 'REGISTERED', data.message || 'Face registered successfully!');
                } else {
                    showCenterToast('error', 'FAILED', data.message || 'Registration failed');
                }
            } catch (error) {
                console.error("Registration error:", error);
                showCenterToast('error', 'SERVER ERROR', 'Failed to process face registration');
            }
        }

        window.addEventListener('load', async () => {
            lucide.createIcons();

            const video = document.getElementById('webcam');
            const overlay = document.getElementById('overlay');
            const statusText = document.getElementById('status');
            const resultText = document.getElementById('result');
            const accessStatusText = document.getElementById('access-status');
            const positionGuide = document.getElementById('position-guide');
            const registerContainer = document.getElementById('register-container');
            const activeEmployeeInput = document.getElementById('activeEmployeeId');

            const MODEL_URL = "{{ asset('models') }}";

            if (typeof faceapi === 'undefined') {
                statusText.innerHTML = `<i data-lucide="alert-triangle"></i> Error loading face-api.js`;
                statusText.style.color = "#ef4444";
                lucide.createIcons();
                return;
            }

            try {
                statusText.innerHTML = `<i data-lucide="loader-2"></i> Loading Models...`;
                lucide.createIcons();

                await Promise.all([
                    faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL),
                    faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                    faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
                ]);

                statusText.innerHTML = `<i data-lucide="activity"></i> System Active`;
                statusText.style.color = "#4ade80";
                lucide.createIcons();
                startVideo();
            } catch (err) {
                console.error("Initialization error:", err);
                statusText.innerHTML = `<i data-lucide="alert-triangle"></i> Error loading models`;
                statusText.style.color = "#ef4444";
                lucide.createIcons();
            }

            function startVideo() {
                navigator.mediaDevices.getUserMedia({
                        video: true
                    })
                    .then(stream => {
                        video.srcObject = stream;
                        video.onloadedmetadata = () => {
                            video.play();
                        };
                    })
                    .catch(err => {
                        console.error("Webcam access error:", err);
                        statusText.innerHTML = `<i data-lucide="camera-off"></i> Camera Access Error`;
                        statusText.style.color = "#ef4444";
                        lucide.createIcons();
                    });
            }

            video.addEventListener('play', () => {
                const detectionOptions = new faceapi.SsdMobilenetv1Options({
                    minConfidence: 0.5
                });
                const displaySize = {
                    width: video.width,
                    height: video.height
                };
                faceapi.matchDimensions(overlay, displaySize);

                setInterval(async () => {
                    const ctx = overlay.getContext('2d');
                    ctx.clearRect(0, 0, overlay.width, overlay.height);

                    if (video.paused || video.ended || isProcessing) return;

                    const detection = await faceapi.detectSingleFace(video, detectionOptions)
                        .withFaceLandmarks()
                        .withFaceDescriptor();

                    if (detection) {
                        const resizedDetection = faceapi.resizeResults(detection, displaySize);
                        const box = resizedDetection.detection.box;

                        const drawBox = new faceapi.draw.DrawBox(box, {
                            label: 'Face',
                            boxColor: '#38bdf8'
                        });
                        drawBox.draw(overlay);

                        const faceWidth = box.width;

                        if (faceWidth < MIN_FACE_WIDTH) {
                            hasBlinked = false;
                            isEyeClosed = false;
                            positionGuide.innerHTML = `<i data-lucide="move-right"></i> Please move closer`;
                            positionGuide.style.color = "#fbbf24";
                            accessStatusText.innerHTML = `<i data-lucide="clock"></i> WAITING`;
                            accessStatusText.className = "";
                            resultText.innerText = "Identity: Positioning face...";
                            setActionButtonsState(false);
                            lucide.createIcons();
                        } else if (faceWidth > MAX_FACE_WIDTH) {
                            hasBlinked = false;
                            isEyeClosed = false;
                            positionGuide.innerHTML = `<i data-lucide="move-left"></i> Please step back`;
                            positionGuide.style.color = "#fbbf24";
                            accessStatusText.innerHTML = `<i data-lucide="clock"></i> WAITING`;
                            accessStatusText.className = "";
                            resultText.innerText = "Identity: Positioning face...";
                            setActionButtonsState(false);
                            lucide.createIcons();
                        } else {
                            const {
                                hasBlinked: isVerified
                            } = trackBlinks(detection.landmarks);

                            if (!isVerified) {
                                positionGuide.innerHTML = `<i data-lucide="eye"></i> Blink required`;
                                positionGuide.style.color = "#fbbf24";
                                accessStatusText.innerHTML = `<i data-lucide="clock"></i> WAITING`;
                                accessStatusText.className = "";
                                resultText.innerText = "Identity: Verifying liveness...";
                                setActionButtonsState(false);
                                lucide.createIcons();
                                return;
                            }

                            positionGuide.innerHTML = `<i data-lucide="shield-check"></i> Liveness Verified`;
                            positionGuide.style.color = "#4ade80";

                            currentDescriptor = Array.from(detection.descriptor);
                            await identifyFace(currentDescriptor);
                            lucide.createIcons();
                        }
                    } else {
                        hasBlinked = false;
                        isEyeClosed = false;
                        positionGuide.innerHTML = `<i data-lucide="user-x"></i> No face detected`;
                        positionGuide.style.color = "#94a3b8";

                        accessStatusText.innerHTML = `<i data-lucide="user-x"></i> NO FACE DETECTED`;
                        accessStatusText.className = "";
                        resultText.innerText = "Identity: Waiting for face...";
                        registerContainer.style.display = "none";
                        activeEmployeeInput.value = "";
                        setActionButtonsState(false);
                        lucide.createIcons();
                    }
                }, 80);
            });

            async function identifyFace(descriptor) {
                isProcessing = true;
                try {
                    const response = await fetch('/face-identify', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            face_descriptor: descriptor
                        })
                    });

                    const data = await response.json();

                    if (data.status === 'match' && data.distance <= 0.45) {
                        accessStatusText.innerHTML = `<i data-lucide="check-circle-2"></i> ACCESS GRANTED`;
                        accessStatusText.className = "access-granted";
                        resultText.innerText = `Identity: ${data.person_name}`;
                        activeEmployeeInput.value = data.employee ? data.employee.id : '';
                        registerContainer.style.display = "none";
                        setActionButtonsState(true);
                    } else {
                        accessStatusText.innerHTML = `<i data-lucide="x-circle"></i> ACCESS DENIED`;
                        accessStatusText.className = "access-denied";
                        resultText.innerText = "Identity: Unknown Person";
                        registerContainer.style.display = "block";
                        activeEmployeeInput.value = "";
                        setActionButtonsState(false);
                    }
                } catch (error) {
                    console.error("Identification error:", error);
                } finally {
                    isProcessing = false;
                    lucide.createIcons();
                }
            }
        });
    </script>
</body>

</html>