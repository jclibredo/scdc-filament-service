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
    <input type="hidden" id="activeEmployeeId" value="">

    <!-- Centered Smooth Fading Toast -->
    <div id="toast-container" class="toast-info">
        <i id="toast-icon" data-lucide="info"></i>
        <div id="toast-message">Initializing...</div>
    </div>

    <!-- Main Kiosk Frame -->
    <div class="registration-card">
        <header>
            <h2>SURGE Identification</h2>
            <p style="color: #94a3b8; font-size: 0.9rem; margin-block-start: 4px;">Real-Time Facial Identification</p>
        </header>

        <!-- Attendance Action Dropdown -->
        <div class="form-group" style="margin-block-start: 1rem;">
            <label for="attendance-type-select">
                <i data-lucide="list-checks" style="vertical-align: middle; inline-size: 18px; block-size: 18px; margin-inline-end: 4px;"></i>
                Select Log Type
            </label>
            <select id="attendance-type-select" class="form-control action-select">
                <option value="time_in" selected>TIME IN</option>
                <option value="break_out">BREAK OUT</option>
                <option value="break_in">BREAK IN</option>
                <option value="time_out">TIME OUT</option>
            </select>
        </div>

        <!-- Viewport Area with Guidelines Overlay -->
        <div class="viewport-container">
            <div class="viewport-wrapper">
                <video id="webcam" autoplay muted playsinline></video>
                <canvas id="overlay-canvas"></canvas>

                <!-- Guidance / Position Overlay Badge -->
                <div id="guidance-overlay" class="status-warn">
                    <i data-lucide="camera"></i>
                    <span>Waiting for camera...</span>
                </div>
            </div>
        </div>

        <!-- Verification Result Status -->
        <div class="status-panel">
            <h3 id="access-status">
                <i data-lucide="clock"></i> WAITING
            </h3>

            <!-- Result Display Card -->
            <div id="result-card" class="result-card-container">
                <p id="result" class="identity-text">Identity: Waiting for detection...</p>
            </div>
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

        function showCenterToast(type, message) {
            const toast = document.getElementById('toast-container');
            const icon = document.getElementById('toast-icon');
            const msgEl = document.getElementById('toast-message');

            clearTimeout(toastTimeout);
            toast.className = '';

            if (type === 'success') {
                toast.classList.add('toast-success');
                icon.setAttribute('data-lucide', 'check-circle-2');
            } else if (type === 'error') {
                toast.classList.add('toast-error');
                icon.setAttribute('data-lucide', 'x-circle');
            } else {
                toast.classList.add('toast-info');
                icon.setAttribute('data-lucide', 'info');
            }

            msgEl.innerText = message;
            lucide.createIcons();
            toast.classList.add('show');

            toastTimeout = setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }

        function updateGuidanceOverlay(statusClass, iconName, message) {
            const overlay = document.getElementById('guidance-overlay');
            overlay.className = statusClass;
            overlay.innerHTML = `<i data-lucide="${iconName}"></i> <span>${message}</span>`;
            lucide.createIcons();
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

        function getActionBadgeColor(type) {
            switch (type) {
                case 'time_in':
                    return 'badge-green';
                case 'break_out':
                    return 'badge-warning';
                case 'break_in':
                    return 'badge-blue';
                case 'time_out':
                    return 'badge-red';
                default:
                    return 'badge-blue';
            }
        }

        function getActionIcon(type) {
            switch (type) {
                case 'time_in':
                    return 'log-in';
                case 'break_out':
                    return 'coffee';
                case 'break_in':
                    return 'utensils';
                case 'time_out':
                    return 'log-out';
                default:
                    return 'check-circle-2';
            }
        }

        async function logAttendance(employeeId, employeeName) {
            const accessStatusText = document.getElementById('access-status');
            const resultCard = document.getElementById('result-card');
            const selectType = document.getElementById('attendance-type-select').value;
            const readableAction = selectType.replace('_', ' ').toUpperCase();

            const now = new Date();
            const localTimestamp = now.getFullYear() + '-' +
                String(now.getMonth() + 1).padStart(2, '0') + '-' +
                String(now.getDate()).padStart(2, '0') + ' ' +
                String(now.getHours()).padStart(2, '0') + ':' +
                String(now.getMinutes()).padStart(2, '0') + ':' +
                String(now.getSeconds()).padStart(2, '0');

            const formattedDateTime = now.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric'
            }) + ' ' + now.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });

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
                        type: selectType,
                        logged_at: localTimestamp
                    })
                });

                const data = await response.json();

                if (response.ok) {
                    accessStatusText.innerHTML = `<i data-lucide="check-circle-2"></i> VERIFIED & LOGGED`;
                    accessStatusText.className = "access-granted";

                    const badgeClass = getActionBadgeColor(selectType);
                    const actionIcon = getActionIcon(selectType);

                    resultCard.innerHTML = `
                        <div class="verified-info-box">
                            <div class="action-tag ${badgeClass}">
                                <i data-lucide="${actionIcon}"></i> ${readableAction}
                            </div>
                            <div class="info-row">
                                <span class="info-label"><i data-lucide="user"></i> NAME:</span>
                                <span class="info-value">${employeeName}</span>
                            </div>
                            <div class="info-row">
                                <span class="info-label"><i data-lucide="calendar"></i> TIME:</span>
                                <span class="info-value">${formattedDateTime}</span>
                            </div>
                        </div>
                    `;

                    showCenterToast('success', `${readableAction} Recorded for ${employeeName}`);
                    await new Promise(res => setTimeout(res, 3000));
                } else {
                    accessStatusText.innerHTML = `<i data-lucide="alert-triangle"></i> LOG FAILED`;
                    accessStatusText.className = "access-denied";
                    resultCard.innerHTML = `<p class="identity-text text-danger"><i data-lucide="x-circle"></i> ${data.message || "Failed to log attendance entry."}</p>`;
                    showCenterToast('error', data.message || "Could not log entry");
                    await new Promise(res => setTimeout(res, 2000));
                }
            } catch (error) {
                console.error("Attendance logging error:", error);
                accessStatusText.innerHTML = `<i data-lucide="x-circle"></i> SERVER ERROR`;
                accessStatusText.className = "access-denied";
                resultCard.innerHTML = `<p class="identity-text text-danger"><i data-lucide="wifi-off"></i> Error connecting to attendance service.</p>`;
                showCenterToast('error', 'Failed to connect to server');
                await new Promise(res => setTimeout(res, 2000));
            } finally {
                lucide.createIcons();
                hasBlinked = false;
                isEyeClosed = false;
            }
        }

        window.addEventListener('load', async () => {
            lucide.createIcons();

            const video = document.getElementById('webcam');
            const overlay = document.getElementById('overlay-canvas');
            const resultCard = document.getElementById('result-card');
            const accessStatusText = document.getElementById('access-status');
            const activeEmployeeInput = document.getElementById('activeEmployeeId');

            const MODEL_URL = "{{ asset('models') }}";

            if (typeof faceapi === 'undefined') {
                updateGuidanceOverlay('status-danger', 'alert-triangle', 'Error loading face-api.js');
                return;
            }

            try {
                updateGuidanceOverlay('status-warn', 'loader-2', 'Loading Models...');

                await Promise.all([
                    faceapi.nets.ssdMobilenetv1.loadFromUri(MODEL_URL),
                    faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                    faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL)
                ]);

                updateGuidanceOverlay('status-ok', 'activity', 'System Active');
                startVideo();
            } catch (err) {
                console.error("Initialization error:", err);
                updateGuidanceOverlay('status-danger', 'alert-triangle', 'Error loading models');
            }

            function startVideo() {
                navigator.mediaDevices.getUserMedia({
                        video: true
                    })
                    .then(stream => {
                        video.srcObject = stream;
                    })
                    .catch(err => {
                        console.error("Webcam access error:", err);
                        updateGuidanceOverlay('status-danger', 'camera-off', 'Camera Access Error');
                    });
            }

            video.addEventListener('play', () => {
                const detectionOptions = new faceapi.SsdMobilenetv1Options({
                    minConfidence: 0.5
                });

                setInterval(async () => {
                    if (video.paused || video.ended) return;

                    // Dynamically set canvas size to match video rendering size
                    const displaySize = {
                        width: video.videoWidth || video.clientWidth || 640,
                        height: video.videoHeight || video.clientHeight || 400
                    };

                    if (overlay.width !== displaySize.width || overlay.height !== displaySize.height) {
                        faceapi.matchDimensions(overlay, displaySize);
                    }

                    const ctx = overlay.getContext('2d');
                    ctx.clearRect(0, 0, overlay.width, overlay.height);

                    if (isProcessing) return;

                    const detection = await faceapi.detectSingleFace(video, detectionOptions)
                        .withFaceLandmarks()
                        .withFaceDescriptor();

                    if (detection) {
                        const resizedDetection = faceapi.resizeResults(detection, displaySize);
                        const box = resizedDetection.detection.box;

                        // Draw explicit green detection box
                        const drawBox = new faceapi.draw.DrawBox(box, {
                            label: 'Face Detected',
                            boxColor: '#22c55e', // Green bounding box color
                            lineWidth: 2
                        });
                        drawBox.draw(overlay);

                        const faceWidth = box.width;

                        if (faceWidth < MIN_FACE_WIDTH) {
                            hasBlinked = false;
                            isEyeClosed = false;
                            updateGuidanceOverlay('status-warn', 'move-right', 'Please move closer');
                            accessStatusText.innerHTML = `<i data-lucide="clock"></i> WAITING`;
                            accessStatusText.className = "";
                            resultCard.innerHTML = `<p class="identity-text">Identity: Positioning face...</p>`;
                            lucide.createIcons();
                        } else if (faceWidth > MAX_FACE_WIDTH) {
                            hasBlinked = false;
                            isEyeClosed = false;
                            updateGuidanceOverlay('status-warn', 'move-left', 'Please step back');
                            accessStatusText.innerHTML = `<i data-lucide="clock"></i> WAITING`;
                            accessStatusText.className = "";
                            resultCard.innerHTML = `<p class="identity-text">Identity: Positioning face...</p>`;
                            lucide.createIcons();
                        } else {
                            const {
                                hasBlinked: isVerified
                            } = trackBlinks(detection.landmarks);

                            if (!isVerified) {
                                updateGuidanceOverlay('status-warn', 'eye', 'Blink required');
                                accessStatusText.innerHTML = `<i data-lucide="clock"></i> WAITING`;
                                accessStatusText.className = "";
                                resultCard.innerHTML = `<p class="identity-text">Identity: Verifying liveness...</p>`;
                                lucide.createIcons();
                                return;
                            }

                            updateGuidanceOverlay('status-ok', 'shield-check', 'Liveness Verified');

                            currentDescriptor = Array.from(detection.descriptor);
                            await identifyFace(currentDescriptor);
                        }
                    } else {
                        hasBlinked = false;
                        isEyeClosed = false;
                        updateGuidanceOverlay('status-warn', 'user-x', 'No face detected');

                        accessStatusText.innerHTML = `<i data-lucide="user-x"></i> NO FACE DETECTED`;
                        accessStatusText.className = "";
                        resultCard.innerHTML = `<p class="identity-text">Identity: Waiting for face...</p>`;
                        activeEmployeeInput.value = "";
                        lucide.createIcons();
                    }
                }, 100);
            });

            // async function identifyFace(descriptor) {
            //     isProcessing = true;
            //     try {
            //         const response = await fetch('/face-identify', {
            //             method: 'POST',
            //             headers: {
            //                 'Content-Type': 'application/json',
            //                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            //             },
            //             body: JSON.stringify({
            //                 face_descriptor: descriptor
            //             })
            //         });

            //         const data = await response.json();

            //         if (data.status === 'match' && data.distance <= 0.45) {
            //             const empId = data.employee ? (data.employee.employeeid || data.employee.id) : '';
            //             const empName = data.person_name || 'Unknown';
            //             activeEmployeeInput.value = empId;

            //             await logAttendance(empId, empName);
            //         } else {
            //             accessStatusText.innerHTML = `<i data-lucide="x-circle"></i> ACCESS DENIED`;
            //             accessStatusText.className = "access-denied";
            //             resultCard.innerHTML = `<p class="identity-text text-danger"><i data-lucide="user-x"></i> Identity: Unknown Person</p>`;
            //             activeEmployeeInput.value = "";
            //             lucide.createIcons();

            //             await new Promise(res => setTimeout(res, 2000));
            //             hasBlinked = false;
            //             isEyeClosed = false;
            //         }
            //     } catch (error) {
            //         console.error("Identification error:", error);
            //     } finally {
            //         isProcessing = false;
            //     }
            // }
            async function identifyFace(descriptor) {
                if (isProcessing) return; // Guard clause against duplicate triggers
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
                        const empId = data.employee ? (data.employee.employeeid || data.employee.id) : '';
                        const empName = data.person_name || 'Unknown';
                        activeEmployeeInput.value = empId;

                        // Trigger log attendance while isProcessing is locked
                        await logAttendance(empId, empName);

                        // Cooldown pause after successful log before allowing another detection
                        await new Promise(res => setTimeout(res, 3000));
                        hasBlinked = false;
                        isEyeClosed = false;
                    } else {
                        accessStatusText.innerHTML = `<i data-lucide="x-circle"></i> ACCESS DENIED`;
                        accessStatusText.className = "access-denied";
                        resultCard.innerHTML = `<p class="identity-text text-danger"><i data-lucide="user-x"></i> Identity: Unknown Person</p>`;
                        activeEmployeeInput.value = "";
                        lucide.createIcons();

                        await new Promise(res => setTimeout(res, 2000));
                        hasBlinked = false;
                        isEyeClosed = false;
                    }
                } catch (error) {
                    console.error("Identification error:", error);
                } finally {
                    isProcessing = false; // Release lock only after process and cooldown finish
                }
            }
        });
    </script>
</body>

</html>