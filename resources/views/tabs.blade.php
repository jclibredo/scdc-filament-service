<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Laravel') }} - Tabbed Navigation</title>

    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-hover: #4338ca;
            --bg-color: #f3f4f6;
            --card-bg: #ffffff;
            --text-main: #1f2937;
            --text-muted: #6b7280;
            --border-color: #e5e7eb;
            --radius: 8px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            width: 100%;
            max-width: 800px;
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
        }

        /* Tab Header Styling */
        .tab-navigation {
            display: flex;
            background-color: #f9fafb;
            border-bottom: 1px solid var(--border-color);
            overflow-x: auto;
        }

        .tab-btn {
            flex: 1;
            padding: 16px 20px;
            border: none;
            background: transparent;
            font-size: 1rem;
            font-weight: 500;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s ease;
            text-align: center;
            white-space: nowrap;
            border-bottom: 2px solid transparent;
        }

        .tab-btn:hover {
            color: var(--primary-color);
            background-color: rgba(79, 70, 229, 0.05);
        }

        .tab-btn.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
            background-color: var(--card-bg);
        }

        /* Tab Body Styling */
        .tab-content {
            padding: 32px;
        }

        .tab-pane {
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }

        .tab-pane.active {
            display: block;
        }

        .tab-pane h2 {
            margin-bottom: 12px;
            font-size: 1.5rem;
            color: var(--text-main);
        }

        .tab-pane p {
            line-height: 1.6;
            color: var(--text-muted);
        }

        /* Simple form styling for Contact Tab */
        .contact-form {
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin-top: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-size: 0.875rem;
            font-weight: 500;
        }

        .form-group input,
        .form-group textarea {
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
        }

        .submit-btn {
            align-self: flex-start;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
        }

        .submit-btn:hover {
            background-color: var(--primary-hover);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(4px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <!-- Navigation Buttons -->
        <nav class="tab-navigation" role="tablist">
            <button class="tab-btn active" onclick="switchTab(event, 'home')">Home</button>
            <button class="tab-btn" onclick="switchTab(event, 'events')">Events</button>
            <button class="tab-btn" onclick="switchTab(event, 'projects')">Projects</button>
            <button class="tab-btn" onclick="switchTab(event, 'contact')">Contact</button>
        </nav>

        <!-- Content Panes -->
        <div class="tab-content">
            <!-- Home Tab -->
            <div id="home" class="tab-pane active" role="tabpanel">
                <h2>Welcome Home</h2>
                <p>Welcome to our platform! Here you can find an overview of our latest updates, announcements, and featured highlights.</p>
            </div>

            <!-- Events Tab -->
            <div id="events" class="tab-pane" role="tabpanel">
                <h2>Upcoming Events</h2>
                <p>Stay tuned for upcoming community meetups, webinars, and technical workshops scheduled for this month.</p>
            </div>

            <!-- Projects Tab -->
            <div id="projects" class="tab-pane" role="tabpanel">
                <h2>Our Projects</h2>
                <p>Explore our recent open-source tools, client solutions, and active developmental roadmaps built with Laravel.</p>
            </div>

            <!-- Contact Tab -->
            <div id="contact" class="tab-pane" role="tabpanel">
                <h2>Get in Touch</h2>
                <p>Have questions or want to collaborate? Send us a message below.</p>

                <form class="contact-form" onsubmit="event.preventDefault(); alert('Message sent!');">
                    @csrf
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" required placeholder="Your Name">
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" required placeholder="your.email@example.com">
                    </div>
                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" rows="4" required placeholder="How can we help?"></textarea>
                    </div>
                    <button type="submit" class="submit-btn">Send Message</button>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript Tab Switching Script -->
    <script>
        function switchTab(event, tabId) {
            // Hide all tab content panes
            const panes = document.querySelectorAll('.tab-pane');
            panes.forEach(pane => pane.classList.remove('active'));

            // Remove active class from all buttons
            const buttons = document.querySelectorAll('.tab-btn');
            buttons.forEach(btn => btn.classList.remove('active'));

            // Show the selected tab content and highlight the button
            document.getElementById(tabId).classList.add('active');
            event.currentTarget.classList.add('active');
        }
    </script>
</body>

</html>