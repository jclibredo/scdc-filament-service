<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sincere Construction & Development Corporation | Plumbing & Fire Protection</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #0f2b48;          /* Deep Industrial Blue */
            --accent: #dc2626;           /* Fire Protection Red Accent */
            --accent-hover: #b91c1c;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --bg-glass: rgba(255, 255, 255, 0.94);
            --border-glass: rgba(255, 255, 255, 0.6);
            --radius-lg: 16px;
            --radius-md: 10px;
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.25), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            /* Background image featuring water supply pipes with control valves */
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.88), rgba(15, 43, 72, 0.85)), 
                        url('https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&w=1920&q=80') center/cover no-repeat fixed;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Top Header Bar */
        .site-header {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.08);
            padding: 14px 40px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        /* Branding: Logo + Title */
        .header-brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .logo-img {
            height: 44px;
            width: auto;
            object-fit: contain;
            border-radius: 6px;
        }

        .brand-text h1 {
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            line-height: 1.2;
        }

        .brand-text p {
            font-size: 0.75rem;
            color: var(--accent);
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* Navigation Tabs */
        .tab-navigation {
            display: flex;
            gap: 8px;
            overflow-x: auto;
        }

        .tab-btn {
            padding: 10px 20px;
            border: none;
            background: transparent;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-muted);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .tab-btn:hover {
            color: var(--primary);
            background: rgba(15, 43, 72, 0.06);
            transform: translateY(-1px);
        }

        .tab-btn.active {
            color: #ffffff;
            background: var(--primary);
            box-shadow: 0 4px 12px rgba(15, 43, 72, 0.3);
        }

        /* Main Container Floating Card */
        .main-card {
            width: 100%;
            max-width: 1000px;
            background: var(--bg-glass);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border-glass);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            margin: 40px 20px;
        }

        /* Content Container */
        .tab-content {
            padding: 40px;
        }

        .tab-pane {
            display: none;
            animation: slideUp 0.4s ease-out forwards;
        }

        .tab-pane.active {
            display: block;
        }

        .section-header {
            margin-bottom: 24px;
        }

        .section-header h2 {
            font-size: 1.8rem;
            color: var(--primary);
            font-weight: 800;
        }

        .section-header p {
            color: var(--text-muted);
            font-size: 0.95rem;
            margin-top: 4px;
        }

        /* Hero Banner */
        .hero-banner {
            position: relative;
            height: 240px;
            border-radius: var(--radius-md);
            overflow: hidden;
            margin-bottom: 28px;
        }

        .hero-banner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .hero-banner:hover img {
            transform: scale(1.04);
        }

        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(15, 43, 72, 0.92), transparent);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 24px;
            color: white;
        }

        .hero-overlay h3 {
            font-size: 1.4rem;
            font-weight: 700;
        }

        .hero-overlay p {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        /* Cards Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 16px;
        }

        .content-card {
            background: #ffffff;
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.06);
        }

        .content-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12);
        }

        .card-img-wrapper {
            height: 160px;
            overflow: hidden;
        }

        .card-img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .content-card:hover .card-img-wrapper img {
            transform: scale(1.08);
        }

        .card-body {
            padding: 18px;
        }

        .card-body h3 {
            font-size: 1.1rem;
            color: var(--primary);
            margin-bottom: 8px;
            font-weight: 700;
        }

        .card-body p {
            font-size: 0.875rem;
            color: var(--text-muted);
            line-height: 1.5;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            background: rgba(220, 38, 38, 0.12);
            color: var(--accent);
            font-size: 0.75rem;
            font-weight: 700;
            border-radius: 4px;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        /* Contact Section */
        .contact-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 6px;
        }

        .form-group input, 
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 0.9rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: #f8fafc;
        }

        .form-group input:focus, 
        .form-group textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(15, 43, 72, 0.15);
            background: #ffffff;
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s;
        }

        .btn-submit:hover {
            background: #1e3a8a;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .site-header {
                flex-direction: column;
                gap: 16px;
                padding: 16px 20px;
                align-items: flex-start;
            }

            .tab-navigation {
                width: 100%;
            }

            .main-card {
                margin: 20px 10px;
            }

            .contact-layout {
                grid-template-columns: 1fr;
            }
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <!-- Header at the Very Top -->
    <header class="site-header">
        <div class="header-brand">
            <img src="{{ asset('images/scdc.jpg') }}" alt="SCDC Logo" class="logo-img">
            <div class="brand-text">
                <h1>Sincere Construction</h1>
                <p>Plumbing & Fire Protection</p>
            </div>
        </div>

        <nav class="tab-navigation" role="tablist">
            <button class="tab-btn active" onclick="switchTab(event, 'home')">Home</button>
            <button class="tab-btn" onclick="switchTab(event, 'events')">Events</button>
            <button class="tab-btn" onclick="switchTab(event, 'projects')">Projects</button>
            <button class="tab-btn" onclick="switchTab(event, 'contact')">Contact</button>
        </nav>
    </header>

    <!-- Main Content Card -->
    <main class="main-card">
        <div class="tab-content">
            
            <!-- HOME TAB -->
            <div id="home" class="tab-pane active" role="tabpanel">
                <div class="hero-banner">
                    <img src="https://images.unsplash.com/photo-1581094794329-c8112a89af12?auto=format&fit=crop&w=1200&q=80" alt="Plumbing Valves and Water Pipe Controls">
                    <div class="hero-overlay">
                        <h3>Precision Piping & Life Safety Engineering</h3>
                        <p>Specialized sanitary plumbing, water distribution, and automatic fire sprinkler installations.</p>
                    </div>
                </div>
                <div class="section-header">
                    <h2>Welcome to SINCERE Construction</h2>
                    <p>Sincere Construction and Development Corporation is a trusted contractor specializing in commercial plumbing infrastructure, water control valves, industrial pipework installation, and comprehensive fire protection systems.</p>
                </div>
            </div>

            <!-- EVENTS TAB -->
            <div id="events" class="tab-pane" role="tabpanel">
                <div class="section-header">
                    <h2>Safety Inspections & Field Works</h2>
                    <p>System testing, NFPA compliance audits, and site commissioning milestones.</p>
                </div>

                <div class="cards-grid">
                    <article class="content-card">
                        <div class="card-img-wrapper">
                            <img src="https://images.unsplash.com/photo-1621905251189-08b45d6a269e?auto=format&fit=crop&w=600&q=80" alt="Fire Sprinkler System Inspection">
                        </div>
                        <div class="card-body">
                            <span class="badge">Fire Protection</span>
                            <h3>Sprinkler Flow & Hydro-Testing</h3>
                            <p>Pressure testing and line flushing for commercial fire suppression distribution headers.</p>
                        </div>
                    </article>

                    <article class="content-card">
                        <div class="card-img-wrapper">
                            <img src="https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&w=600&q=80" alt="Water Pipework and Valve Installation">
                        </div>
                        <div class="card-body">
                            <span class="badge">Sanitary Engineering</span>
                            <h3>Commercial Water Main Assembly</h3>
                            <p>Installation of high-pressure backflow preventers, control valves, and municipal water service connections.</p>
                        </div>
                    </article>
                </div>
            </div>

            <!-- PROJECTS TAB -->
            <div id="projects" class="tab-pane" role="tabpanel">
                <div class="section-header">
                    <h2>Featured Mechanical & Piping Contracts</h2>
                    <p>Highlights of recent plumbing installations and fire protection systems.</p>
                </div>

                <div class="cards-grid">
                    <article class="content-card">
                        <div class="card-img-wrapper">
                            <img src="https://images.unsplash.com/photo-1542013936693-884638332954?auto=format&fit=crop&w=600&q=80" alt="Industrial Pipework with Control Valves">
                        </div>
                        <div class="card-body">
                            <span class="badge">Industrial Piping</span>
                            <h3>High-Rise Fire Pump & Standpipe Network</h3>
                            <p>Complete installation of electric fire pumps, gate valves, jockey pumps, and vertical standpipe risers.</p>
                        </div>
                    </article>

                    <article class="content-card">
                        <div class="card-img-wrapper">
                            <img src="https://images.unsplash.com/photo-1584622650111-993a426fbf0a?auto=format&fit=crop&w=600&q=80" alt="Commercial Water Valves System">
                        </div>
                        <div class="card-body">
                            <span class="badge">Plumbing Contract</span>
                            <h3>Hospitality Water Supply & Drainage</h3>
                            <p>Engineered multi-floor domestic hot/cold water supply, pressure regulator valves, and grease trap filtration systems.</p>
                        </div>
                    </article>
                </div>
            </div>

            <!-- CONTACT TAB -->
            <div id="contact" class="tab-pane" role="tabpanel">
                <div class="section-header">
                    <h2>Request a MEPF Proposal</h2>
                    <p>Contact our plumbing and fire protection engineering team for bids and technical consultations.</p>
                </div>

                <div class="contact-layout">
                    <form onsubmit="event.preventDefault(); alert('Inquiry submitted to Sincere Construction Engineering team!');">
                        @csrf
                        <div class="form-group">
                            <label for="name">Name / Company Representative</label>
                            <input type="text" id="name" placeholder="John Doe" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Business Email</label>
                            <input type="email" id="email" placeholder="john@company.com" required>
                        </div>
                        <div class="form-group">
                            <label for="message">Project Scope / Piping Requirements</label>
                            <textarea id="message" rows="4" placeholder="Specify plumbing, water valve assembly, fire sprinkler, or standpipe requirements..." required></textarea>
                        </div>
                        <button type="submit" class="btn-submit">Submit Proposal Request</button>
                    </form>

                    <div style="border-radius: var(--radius-md); overflow: hidden; height: 100%; min-height: 220px;">
                        <img src="https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&w=600&q=80" alt="Water Pipework with Control Valves" style="width:100%; height:100%; object-fit:cover;">
                    </div>
                </div>
            </div>

        </div>
    </main>

    <script>
        function switchTab(event, tabId) {
            document.querySelectorAll('.tab-pane').forEach(pane => {
                pane.classList.remove('active');
            });

            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            document.getElementById(tabId).classList.add('active');
            event.currentTarget.classList.add('active');
        }
    </script>
</body>
</html>