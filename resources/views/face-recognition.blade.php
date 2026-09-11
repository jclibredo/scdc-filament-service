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
    <style>
        :root {

            --bg-color: #e2e8f0;

            --frame-bg: #f8fafc;

            --card-bg: #ffffff;

            --text-main: #0f172a;

            --text-muted: #64748b;

            --accent-blue: #0284c7;

            --accent-green: #16a34a;

            --accent-red: #dc2626;

            --accent-warning: #d97706;

            --border-color: #94a3b8;

        }



        * {

            box-sizing: border-box;

            margin: 0;

            padding: 0;

        }



        body {

            font-family: 'Segoe UI', Roboto, -apple-system, sans-serif;

            background-color: var(--bg-color);

            color: var(--text-main);

            min-height: 100vh;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 20px;

        }



        /* Form / Main Frame */

        .kiosk-card {

            width: 100%;

            max-width: 600px;

            background-color: var(--frame-bg);

            border: 3px solid var(--border-color);

            border-radius: 16px;

            padding: 28px;

            display: flex;

            flex-direction: column;

            align-items: center;

            gap: 18px;

            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.05);

        }



        header {

            text-align: center;

        }



        header h2 {

            font-size: 1.5rem;

            font-weight: 700;

            color: var(--text-main);

        }



        header p {

            color: var(--text-muted);

            font-size: 0.85rem;

            margin-top: 2px;

        }



        /* Camera Viewport */

        #video-container {

            position: relative;

            width: 100%;

            height: 420px;

            border-radius: 12px;

            overflow: hidden;

            background: #000;

            border: 2px solid var(--border-color);

            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);

        }



        video {

            width: 100%;

            height: 100%;

            object-fit: cover;

        }



        canvas {

            position: absolute;

            top: 0;

            left: 0;

            width: 100%;

            height: 100%;

            pointer-events: none;

        }



        /* Video Overlay HUD Elements */

        .hud-status-bar {

            position: absolute;

            top: 12px;

            left: 12px;

            right: 12px;

            display: flex;

            justify-content: space-between;

            align-items: center;

            z-index: 10;

        }



        .hud-badge {

            background: rgba(15, 23, 42, 0.75);

            backdrop-filter: blur(8px);

            padding: 6px 12px;

            border-radius: 20px;

            font-size: 0.75rem;

            font-weight: 600;

            color: #ffffff;

            border: 1.5px solid rgba(255, 255, 255, 0.3);

            display: inline-flex;

            align-items: center;

            gap: 6px;

        }



        .hud-guide {

            position: absolute;

            bottom: 12px;

            left: 50%;

            transform: translateX(-50%);

            background: rgba(15, 23, 42, 0.85);

            backdrop-filter: blur(8px);

            padding: 8px 16px;

            border-radius: 20px;

            font-size: 0.85rem;

            font-weight: 600;

            color: #38bdf8;

            border: 1.5px solid rgba(255, 255, 255, 0.3);

            text-align: center;

            white-space: nowrap;

            z-index: 10;

            display: inline-flex;

            align-items: center;

            gap: 6px;

        }



        .hud-guide svg,

        .hud-badge svg {

            width: 18px;

            height: 18px;

        }



        /* Floating Center Toast Notification overlay */

        .center-float-toast {

            position: absolute;

            top: 50%;

            left: 50%;

            transform: translate(-50%, -40%);

            background: rgba(15, 23, 42, 0.92);

            backdrop-filter: blur(12px);

            border: 2px solid rgba(255, 255, 255, 0.2);

            border-radius: 16px;

            padding: 20px 28px;

            display: flex;

            flex-direction: column;

            align-items: center;

            justify-content: center;

            gap: 8px;

            z-index: 25;

            box-shadow: 0 20px 30px rgba(0, 0, 0, 0.5);

            opacity: 0;

            pointer-events: none;

            transition: opacity 0.4s ease, transform 0.4s ease;

        }



        .center-float-toast.show {

            opacity: 1;

            transform: translate(-50%, -50%);

            pointer-events: auto;

        }



        .toast-icon-wrapper {

            width: 52px;

            height: 52px;

            border-radius: 50%;

            display: flex;

            align-items: center;

            justify-content: center;

        }



        .toast-icon-wrapper svg {

            width: 32px;

            height: 32px;

            color: #ffffff;

        }



        .toast-success .toast-icon-wrapper {

            background-color: var(--accent-green);

        }



        .toast-error .toast-icon-wrapper {

            background-color: var(--accent-red);

        }



        .toast-title {

            color: #ffffff;

            font-size: 1.15rem;

            font-weight: 800;

            letter-spacing: 0.5px;

            text-transform: uppercase;

        }



        /* Timestamp Pill in Floating Message */

        .toast-time {

            background: rgba(255, 255, 255, 0.15);

            border: 1px solid rgba(255, 255, 255, 0.25);

            border-radius: 12px;

            padding: 2px 10px;

            font-size: 0.8rem;

            font-weight: 700;

            color: #38bdf8;

            letter-spacing: 0.5px;

            display: inline-flex;

            align-items: center;

            gap: 4px;

        }



        .toast-time svg {

            width: 14px;

            height: 14px;

        }



        .toast-message {

            color: #cbd5e1;

            font-size: 0.85rem;

            text-align: center;

            max-width: 260px;

        }



        /* Verification Info Card */

        .status-panel {

            width: 100%;

            background: var(--card-bg);

            border-radius: 12px;

            padding: 16px;

            text-align: center;

            border: 2px solid var(--border-color);

            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);

        }



        #access-status {

            font-size: 1.25rem;

            font-weight: 800;

            letter-spacing: 0.5px;

            color: var(--text-muted);

            display: flex;

            align-items: center;

            justify-content: center;

            gap: 8px;

        }



        #access-status svg {

            width: 24px;

            height: 24px;

        }



        .access-granted {

            color: var(--accent-green) !important;

        }



        .access-denied {

            color: var(--accent-red) !important;

        }



        .identity-text {

            font-size: 0.9rem;

            color: var(--text-muted);

            margin-top: 4px;

        }



        /* Attendance Actions */

        .attendance-grid {

            display: grid;

            grid-template-columns: repeat(4, 1fr);

            gap: 8px;

            width: 100%;

        }



        .btn-action {

            padding: 12px 6px;

            font-weight: 700;

            font-size: 0.8rem;

            border: 2px solid var(--border-color);

            border-radius: 8px;

            cursor: pointer;

            transition: all 0.2s ease;

            color: #ffffff;

            opacity: 0.4;

            pointer-events: none;

        }



        .btn-action.active {

            opacity: 1;

            pointer-events: auto;

        }



        .btn-action:hover {

            filter: brightness(0.9);

        }



        .btn-time-in {

            background-color: var(--accent-green);

        }



        .btn-break-out {

            background-color: var(--accent-warning);

        }



        .btn-break-in {

            background-color: var(--accent-blue);

        }



        .btn-time-out {

            background-color: var(--accent-red);

        }



        /* Registration Drawer */

        #register-container {

            display: none;

            width: 100%;

            background: var(--card-bg);

            padding: 12px 16px;

            border-radius: 12px;

            border: 2px solid var(--border-color);

            text-align: center;

            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);

        }



        .btn-register {

            width: 100%;

            margin-top: 8px;

            padding: 10px;

            background-color: var(--accent-green);

            color: #ffffff;

            font-weight: 600;

            font-size: 0.85rem;

            border: none;

            border-radius: 6px;

            cursor: pointer;

        }
        @media (max-width: 480px) {

            .attendance-grid {

                grid-template-columns: 1fr 1fr;

            }

        }
    </style>


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