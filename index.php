<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Untalan General Hospital - AppointEase</title>
    <meta name="description" content="Book your medical appointments easily with Untalan General Hospital.">
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        },
                        teal: {
                            50: '#f0fdfa',
                            500: '#14b8a6',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        /* Custom Styles */
        .glass-nav {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(229, 231, 235, 0.5);
        }
        
        .blob-anim {
            animation: blob-bounce 10s infinite ease-in-out alternate;
        }
        @keyframes blob-bounce {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(20px, -20px) scale(1.1); }
        }

        .hero-pattern {
            background-image: radial-gradient(#3b82f6 0.5px, transparent 0.5px), radial-gradient(#3b82f6 0.5px, #ffffff 0.5px);
            background-size: 20px 20px;
            background-position: 0 0, 10px 10px;
            background-color: #ffffff;
            opacity: 1;
        }
        
        /* Smooth fade up animation class */
        .fade-up {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeUpAnim 0.8s forwards ease-out;
        }
        @keyframes fadeUpAnim {
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="text-slate-600 antialiased bg-white selection:bg-blue-100 selection:text-blue-900">

    <div class="bg-slate-900 text-white text-xs font-medium py-2 px-4 text-center">
        <span class="inline-flex items-center gap-2">
            <i data-lucide="alert-circle" class="w-4 h-4 text-red-400"></i>
            For medical emergencies, please call <strong>911</strong> or our emergency hotline <strong>(043) 999-9999</strong> immediately.
        </span>
    </div>

    <nav class="fixed w-full z-50 glass-nav transition-all duration-300 top-0" id="navbar">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20" id="nav-container">
                <div class="flex-shrink-0 flex items-center gap-3 cursor-pointer group" onclick="window.scrollTo(0,0)">
                    <div class="bg-gradient-to-br from-blue-600 to-blue-700 p-2.5 rounded-xl text-white shadow-lg shadow-blue-500/20 group-hover:shadow-blue-500/30 transition-all">
                        <i data-lucide="activity" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-slate-900 leading-none tracking-tight">Untalan<span class="text-blue-600">GH</span></h1>
                        <p class="text-[10px] text-slate-500 font-bold tracking-widest uppercase mt-0.5">AppointEase System</p>
                    </div>
                </div>

                <div class="hidden md:flex items-center space-x-8">
                    <a href="#how-it-works" class="text-sm font-semibold text-slate-600 hover:text-blue-600 transition">How it works</a>
                    <a href="#features" class="text-sm font-semibold text-slate-600 hover:text-blue-600 transition">Services</a>
                    <a href="#contact" class="text-sm font-semibold text-slate-600 hover:text-blue-600 transition">Contact</a>
                    
                    <div class="flex items-center space-x-3 ml-6 pl-6 border-l border-slate-200">
                        <a href="login.php" class="text-sm font-bold text-slate-700 hover:text-blue-600 transition px-3 py-2">Log In</a>
                        <a href="signup.php" class="px-5 py-2.5 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-full transition shadow-lg shadow-blue-600/20 hover:shadow-blue-600/30 ring-2 ring-blue-600 ring-offset-2">
                            Book Appointment
                        </a>
                    </div>
                </div>

                <div class="md:hidden flex items-center">
                    <button id="mobile-menu-btn" class="text-slate-600 hover:text-blue-600 focus:outline-none p-2 rounded-lg hover:bg-slate-100">
                        <i data-lucide="menu" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>
        </div>

        <div id="mobile-menu" class="hidden md:hidden bg-white border-t border-gray-100 absolute w-full shadow-xl">
            <div class="px-4 pt-2 pb-6 space-y-2">
                <a href="#how-it-works" class="block px-3 py-3 text-base font-medium text-slate-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg">How it works</a>
                <a href="#features" class="block px-3 py-3 text-base font-medium text-slate-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg">Services</a>
                <a href="#contact" class="block px-3 py-3 text-base font-medium text-slate-700 hover:text-blue-600 hover:bg-blue-50 rounded-lg">Contact</a>
                <div class="mt-4 pt-4 border-t border-gray-100 flex flex-col space-y-3">
                    <a href="login.php" class="block w-full text-center px-4 py-3 text-slate-700 font-bold border border-slate-200 rounded-xl hover:bg-slate-50">Log In</a>
                    <a href="signup.php" class="block w-full text-center px-4 py-3 text-white bg-blue-600 font-bold rounded-xl hover:bg-blue-700 shadow-md">Book Appointment</a>
                </div>
            </div>
        </div>
    </nav>

    <section class="relative pt-32 pb-20 lg:pt-48 lg:pb-32 overflow-hidden">
        <div class="absolute inset-0 -z-20 h-full w-full bg-white bg-[radial-gradient(#e5e7eb_1px,transparent_1px)] [background-size:16px_16px] [mask-image:radial-gradient(ellipse_50%_50%_at_50%_50%,#000_70%,transparent_100%)]"></div>
        <div class="absolute top-0 right-0 -translate-y-12 translate-x-12 blob-anim -z-10 opacity-30">
            <div class="w-96 h-96 bg-blue-400 rounded-full blur-3xl"></div>
        </div>
        <div class="absolute bottom-0 left-0 translate-y-12 -translate-x-12 blob-anim -z-10 opacity-30 animation-delay-2000">
            <div class="w-80 h-80 bg-teal-300 rounded-full blur-3xl"></div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-8 items-center">
                <div class="text-center lg:text-left fade-up">
                    <div class="inline-flex items-center px-3 py-1 rounded-full bg-blue-50 border border-blue-100 text-blue-700 text-xs font-bold uppercase tracking-wide mb-6">
                        <span class="flex h-2 w-2 relative mr-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-blue-500"></span>
                        </span>
                        Online Scheduling Live
                    </div>

                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-slate-900 mb-6 leading-[1.15]">
                        Healthcare aimed at <br class="hidden lg:block"/>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-teal-500">your convenience.</span>
                    </h1>

                    <p class="mt-4 text-lg text-slate-500 mb-8 leading-relaxed max-w-2xl mx-auto lg:mx-0">
                        Skip the waiting room. Book appointments with Untalan General Hospital's top specialists securely from your home.
                    </p>

                    <div class="flex flex-col sm:flex-row justify-center lg:justify-start gap-4">
                        <a href="signup.php" class="inline-flex items-center justify-center px-8 py-4 text-base font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 transition shadow-xl shadow-blue-600/20 transform hover:-translate-y-1">
                            Find a Doctor
                            <i data-lucide="arrow-right" class="ml-2 w-5 h-5"></i>
                        </a>
                        <a href="#features" class="inline-flex items-center justify-center px-8 py-4 text-base font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition hover:border-slate-300">
                            View Services
                        </a>
                    </div>

                    <div class="mt-10 pt-8 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-6 text-sm font-medium text-slate-500">
                        <div class="flex items-center gap-2">
                            <i data-lucide="shield-check" class="w-5 h-5 text-teal-500"></i>
                            <span>HIPAA Compliant</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i data-lucide="users" class="w-5 h-5 text-blue-500"></i>
                            <span>10k+ Patients Served</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i data-lucide="clock" class="w-5 h-5 text-blue-500"></i>
                            <span>24/7 Booking</span>
                        </div>
                    </div>
                </div>

                <div class="relative lg:h-[600px] flex items-center justify-center fade-up" style="animation-delay: 0.2s;">
                    <div class="absolute w-[500px] h-[500px] bg-gradient-to-tr from-blue-100 to-teal-50 rounded-full opacity-50 blur-3xl -z-10"></div>
                    
                    <div class="relative bg-white rounded-3xl shadow-2xl border border-slate-100 p-6 w-full max-w-md transform rotate-[-2deg] hover:rotate-0 transition duration-500">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center text-blue-600">
                                    <i data-lucide="user" class="w-6 h-6"></i>
                                </div>
                                <div>
                                    <div class="h-2.5 w-24 bg-slate-800 rounded mb-1.5"></div>
                                    <div class="h-2 w-16 bg-slate-300 rounded"></div>
                                </div>
                            </div>
                            <div class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs font-bold">Confirmed</div>
                        </div>
                        
                        <div class="bg-slate-50 rounded-2xl p-4 mb-6 border border-slate-100">
                            <div class="flex justify-between items-center mb-4">
                                <span class="font-bold text-slate-700">December 2025</span>
                                <div class="flex gap-1">
                                    <div class="w-6 h-6 rounded bg-white shadow-sm"></div>
                                    <div class="w-6 h-6 rounded bg-white shadow-sm"></div>
                                </div>
                            </div>
                            <div class="grid grid-cols-7 gap-2 text-center text-xs text-slate-400">
                                <div>M</div><div>T</div><div>W</div><div>T</div><div>F</div><div>S</div><div>S</div>
                                <div>1</div><div>2</div>
                                <div class="bg-blue-600 text-white rounded-lg shadow-md shadow-blue-200 py-1 font-bold">3</div>
                                <div>4</div><div>5</div><div>6</div><div>7</div>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 p-4 rounded-xl bg-blue-50 border border-blue-100">
                            <div class="w-14 h-14 bg-white rounded-xl shadow-sm flex items-center justify-center text-2xl">👨‍⚕️</div>
                            <div class="flex-1">
                                <h4 class="font-bold text-slate-800">Dr. Sarah Lopez</h4>
                                <p class="text-xs text-blue-600 font-semibold uppercase">Cardiologist</p>
                                <div class="flex items-center gap-1 mt-1 text-xs text-slate-500">
                                    <i data-lucide="clock" class="w-3 h-3"></i> 10:00 AM - 11:00 AM
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="absolute -right-4 bottom-20 bg-white p-4 rounded-2xl shadow-xl border border-slate-100 animate-bounce" style="animation-duration: 3s;">
                        <div class="flex items-center gap-3">
                            <div class="bg-green-100 p-2 rounded-lg text-green-600">
                                <i data-lucide="check-circle" class="w-6 h-6"></i>
                            </div>
                            <div>
                                <p class="text-xs text-slate-500 font-semibold">Status</p>
                                <p class="font-bold text-slate-800">Appointment Set!</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="how-it-works" class="py-20 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-sm font-bold text-blue-600 tracking-widest uppercase mb-2">Easy Process</h2>
                <h3 class="text-3xl font-bold text-slate-900 sm:text-4xl">Book in 3 Simple Steps</h3>
            </div>

            <div class="grid md:grid-cols-3 gap-8 relative">
                <div class="hidden md:block absolute top-12 left-0 w-full h-0.5 bg-gradient-to-r from-transparent via-slate-300 to-transparent z-0"></div>

                <div class="relative z-10 text-center group">
                    <div class="w-24 h-24 mx-auto bg-white rounded-full flex items-center justify-center shadow-lg border-4 border-slate-50 mb-6 group-hover:border-blue-100 group-hover:scale-110 transition duration-300">
                        <span class="text-4xl font-bold text-blue-600/20 group-hover:text-blue-600 transition">1</span>
                        <i data-lucide="user-plus" class="absolute w-8 h-8 text-blue-600"></i>
                    </div>
                    <h4 class="text-xl font-bold text-slate-900 mb-2">Create Account</h4>
                    <p class="text-slate-500 px-4">Sign up with your basic details to create your secure patient portal.</p>
                </div>

                <div class="relative z-10 text-center group">
                    <div class="w-24 h-24 mx-auto bg-white rounded-full flex items-center justify-center shadow-lg border-4 border-slate-50 mb-6 group-hover:border-blue-100 group-hover:scale-110 transition duration-300">
                        <span class="text-4xl font-bold text-blue-600/20 group-hover:text-blue-600 transition">2</span>
                        <i data-lucide="search" class="absolute w-8 h-8 text-blue-600"></i>
                    </div>
                    <h4 class="text-xl font-bold text-slate-900 mb-2">Find Doctor</h4>
                    <p class="text-slate-500 px-4">Browse our specialists and view their real-time availability calendar.</p>
                </div>

                <div class="relative z-10 text-center group">
                    <div class="w-24 h-24 mx-auto bg-white rounded-full flex items-center justify-center shadow-lg border-4 border-slate-50 mb-6 group-hover:border-blue-100 group-hover:scale-110 transition duration-300">
                        <span class="text-4xl font-bold text-blue-600/20 group-hover:text-blue-600 transition">3</span>
                        <i data-lucide="calendar-check-2" class="absolute w-8 h-8 text-blue-600"></i>
                    </div>
                    <h4 class="text-xl font-bold text-slate-900 mb-2">Book Slot</h4>
                    <p class="text-slate-500 px-4">Select your time, confirm via email, and you're ready to go!</p>
                </div>
            </div>
        </div>
    </section>

    <section id="features" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-sm font-bold text-blue-600 tracking-widest uppercase mb-2">Why Choose Us</h2>
                <h3 class="text-3xl font-bold text-slate-900 sm:text-4xl">
                    Comprehensive Care, Digital Ease
                </h3>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="group p-8 bg-white rounded-3xl border border-slate-100 shadow-lg shadow-slate-200/50 hover:shadow-xl hover:-translate-y-1 transition duration-300">
                    <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mb-6 text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition">
                        <i data-lucide="smartphone" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-3">Instant Scheduling</h3>
                    <p class="text-slate-500 text-sm leading-relaxed">
                        No more phone tag. See exactly when doctors are free and book the slot that fits your life.
                    </p>
                </div>

                <div class="group p-8 bg-white rounded-3xl border border-slate-100 shadow-lg shadow-slate-200/50 hover:shadow-xl hover:-translate-y-1 transition duration-300">
                    <div class="w-12 h-12 bg-teal-100 rounded-xl flex items-center justify-center mb-6 text-teal-600 group-hover:bg-teal-600 group-hover:text-white transition">
                        <i data-lucide="lock" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-3">Secure Records</h3>
                    <p class="text-slate-500 text-sm leading-relaxed">
                        Bank-level encryption keeps your medical history, prescriptions, and personal data safe.
                    </p>
                </div>

                <div class="group p-8 bg-white rounded-3xl border border-slate-100 shadow-lg shadow-slate-200/50 hover:shadow-xl hover:-translate-y-1 transition duration-300">
                    <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center mb-6 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition">
                        <i data-lucide="stethoscope" class="w-6 h-6"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-3">Expert Specialists</h3>
                    <p class="text-slate-500 text-sm leading-relaxed">
                        Access a directory of board-certified specialists in Cardiology, Pediatrics, Neurology, and more.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section id="contact" class="py-20">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="relative bg-blue-600 rounded-3xl p-8 md:p-16 overflow-hidden shadow-2xl shadow-blue-900/20 text-center">
                <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0">
                    <div class="absolute -top-24 -right-24 w-64 h-64 rounded-full bg-blue-500 opacity-50 blur-2xl"></div>
                    <div class="absolute -bottom-24 -left-24 w-64 h-64 rounded-full bg-teal-500 opacity-50 blur-2xl"></div>
                </div>

                <div class="relative z-10">
                    <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">Ready to prioritize your health?</h2>
                    <p class="text-blue-100 text-lg mb-8 max-w-2xl mx-auto">
                        Join thousands of patients who trust Untalan General Hospital for their healthcare needs. Account creation is free and takes less than 2 minutes.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="signup.php" class="px-8 py-4 bg-white text-blue-700 font-bold rounded-xl hover:bg-slate-50 transition shadow-lg">
                            Create Free Account
                        </a>
                        <a href="login.php" class="px-8 py-4 bg-blue-700 text-white font-bold rounded-xl hover:bg-blue-800 transition border border-blue-500">
                            Log In
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-white border-t border-slate-200 pt-16 pb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-12 mb-12">
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center gap-2 mb-6">
                        <div class="bg-blue-600 p-1.5 rounded-lg text-white">
                            <i data-lucide="activity" class="w-5 h-5"></i>
                        </div>
                        <span class="text-lg font-bold text-slate-900">Untalan General Hospital</span>
                    </div>
                    <p class="text-slate-500 text-sm leading-relaxed max-w-sm">
                        Providing world-class healthcare services with a personal touch. AppointEase makes accessing our facility simpler than ever before.
                    </p>
                </div>

                <div>
                    <h4 class="font-bold text-slate-900 mb-6">Patient Portal</h4>
                    <ul class="space-y-4 text-sm text-slate-600">
                        <li><a href="login.php" class="hover:text-blue-600 transition">Log In</a></li>
                        <li><a href="signup.php" class="hover:text-blue-600 transition">Register</a></li>
                        <li><a href="#" class="hover:text-blue-600 transition">Find a Doctor</a></li>
                        <li><a href="#" class="hover:text-blue-600 transition">Help Center</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold text-slate-900 mb-6">Contact Us</h4>
                    <ul class="space-y-4 text-sm text-slate-600">
                        <li class="flex items-start gap-3">
                            <i data-lucide="map-pin" class="w-5 h-5 text-blue-600 shrink-0"></i>
                            <span>123 Medical Drive, Batangas City, Philippines</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i data-lucide="phone" class="w-5 h-5 text-blue-600 shrink-0"></i>
                            <span>(043) 123-4567</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i data-lucide="mail" class="w-5 h-5 text-blue-600 shrink-0"></i>
                            <span>info@untalangh.com</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-slate-100 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-sm text-slate-400">© 2025 Untalan General Hospital. All rights reserved.</p>
                <div class="flex space-x-6">
                    <a href="#" class="text-slate-400 hover:text-blue-600 transition"><i data-lucide="facebook" class="w-5 h-5"></i></a>
                    <a href="#" class="text-slate-400 hover:text-blue-600 transition"><i data-lucide="twitter" class="w-5 h-5"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Initialize Icons
        lucide.createIcons();

        // Mobile Menu Toggle
        const btn = document.getElementById('mobile-menu-btn');
        const menu = document.getElementById('mobile-menu');

        btn.addEventListener('click', () => {
            menu.classList.toggle('hidden');
        });

        // Close mobile menu when clicking a link
        document.querySelectorAll('#mobile-menu a').forEach(link => {
            link.addEventListener('click', () => {
                menu.classList.add('hidden');
            });
        });

        // Optimized Sticky Navbar
        const navbar = document.getElementById('navbar');
        const navContainer = document.getElementById('nav-container');
        
        // Add scroll padding to prevent content jumping behind fixed header
        document.documentElement.style.scrollPaddingTop = navbar.offsetHeight + 'px';

        window.addEventListener('scroll', () => {
            if (window.scrollY > 20) {
                navbar.classList.add('shadow-md');
                // Optional: Reduce padding on scroll for compact look
                navContainer.classList.remove('h-20');
                navContainer.classList.add('h-16');
            } else {
                navbar.classList.remove('shadow-md');
                navContainer.classList.remove('h-16');
                navContainer.classList.add('h-20');
            }
        });
    </script>
</body>
</html>