lucide.createIcons();
        
        // --- CONSTANTS ---
        const APPOINTMENT_DURATION_MINUTES = 30; // 30 minutes per patient slot
        const daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        let currentDate = new Date();
        let rosterData = [];
        let leaveData = [];

        const monthDisplay = document.getElementById('monthDisplay');
        const grid = document.getElementById('calendarGrid');

        // --- CORE CALCULATION LOGIC ---
        /**
         * Calculates the maximum number of appointments based on the time range.
         * @param {string} startTime - Time string (HH:MM).
         * @param {string} endTime - Time string (HH:MM).
         * @returns {number} Max appointments count (min 1 if valid, 0 if invalid range).
         */
        function calculateMaxPatients(startTime, endTime) {
            // Check for valid time formats
            if (!startTime || !endTime || startTime.length !== 5 || endTime.length !== 5) {
                return 1;
            }

            // Convert times to minutes from midnight
            const [startH, startM] = startTime.split(':').map(Number);
            const [endH, endM] = endTime.split(':').map(Number);

            const startTotalMinutes = startH * 60 + startM;
            let endTotalMinutes = endH * 60 + endM;
            
            // Check for invalid range (End time must be after start time)
            if (endTotalMinutes <= startTotalMinutes) {
                return 0; 
            }

            const durationMinutes = endTotalMinutes - startTotalMinutes;
            
            // Calculate max appointments
            const maxAppointments = Math.floor(durationMinutes / APPOINTMENT_DURATION_MINUTES);

            return Math.max(1, maxAppointments); // Ensure a minimum of 1 if duration is valid
        }

        document.addEventListener('DOMContentLoaded', () => {
            fetchData();
        });

        async function fetchData() {
            monthDisplay.textContent = "Loading...";
            try {
                const response = await fetch(`?action=get_calendar_data`);
                const data = await response.json();
                if (data.status === 'error') throw new Error(data.message);
                rosterData = data.roster || [];
                leaveData = data.leaves || [];
                renderCalendar();
            } catch (error) {
                monthDisplay.textContent = "Error";
                grid.innerHTML = `<div class="col-span-7 py-12 text-center text-red-500">${error.message}</div>`;
            }
        }

        function renderCalendar() {
            grid.innerHTML = '';
            ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].forEach(d => grid.innerHTML += `<div class="day-header">${d}</div>`);

            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            monthDisplay.textContent = new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(currentDate);

            const firstDayIndex = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const today = new Date();
            today.setHours(0,0,0,0);

            for (let i = 0; i < firstDayIndex; i++) {
                grid.appendChild(Object.assign(document.createElement('div'), { className: 'day-cell other-month' }));
            }

            for (let d = 1; d <= daysInMonth; d++) {
                const dateObj = new Date(year, month, d);
                const dateStr = [dateObj.getFullYear(), String(dateObj.getMonth() + 1).padStart(2, '0'), String(dateObj.getDate()).padStart(2, '0')].join('-');
                const dayIndex = dateObj.getDay();
                const isPast = dateObj < today;

                const cell = document.createElement('div');
                cell.className = 'day-cell';
                if (isPast) cell.classList.add('day-past');
                
                let content = `<span class="text-xs font-bold text-slate-400 absolute top-2 left-2">${d}</span>`;

                const leave = leaveData.find(l => l.date_start === dateStr);
                const shift = rosterData.find(r => parseInt(r.day) === dayIndex);

                if (leave) {
                    content += `<div class="mt-6 event-chip event-block">⛔ ${leave.reason}</div>`;
                    cell.style.background = '#fff1f2';
                } else if (shift) {
                    content += `<div class="mt-6 event-chip event-roster">${formatTime(shift.time)}-${formatTime(shift.time2)}</div>`;
                }

                cell.innerHTML = content;
                
                if (!isPast) {
                    cell.onclick = () => openModal(dateStr, dayIndex, shift, leave);
                }

                grid.appendChild(cell);
            }
        }

        function openModal(dateStr, dayIndex, shift, leave) {
            const modal = document.getElementById('slotModal');
            const parts = dateStr.split('-');
            const dateObj = new Date(parts[0], parts[1]-1, parts[2]);
            
            document.getElementById('modalDateDisplay').textContent = dateObj.toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
            document.getElementById('rosterDayName').textContent = daysOfWeek[dayIndex];
            
            // Set Inputs
            document.getElementById('formBlockDate').value = dateStr;
            document.getElementById('formRosterDayIndex').value = dayIndex;
            document.getElementById('formUnblockDate').value = dateStr;

            // Logic: If blocked, show Unblock Form
            const blockForm = document.getElementById('blockForm');
            const unblockForm = document.getElementById('unblockForm');
            
            if(leave) {
                blockForm.classList.add('hidden');
                unblockForm.classList.remove('hidden');
                document.getElementById('blockReasonDisplay').textContent = `Reason: ${leave.reason}`;
                switchModalTab('block');
            } else {
                unblockForm.classList.add('hidden');
                blockForm.classList.remove('hidden');
                switchModalTab('roster'); 
            }

            // Setup Roster UI
            const rosterInputs = document.getElementById('rosterInputs');
            const rosterActive = document.getElementById('rosterActive');
            const rosterStart = document.getElementById('rosterStart');
            const rosterEnd = document.getElementById('rosterEnd');
            const rosterMax = document.getElementById('rosterMax');

            // --- FUNCTION TO UPDATE MAX PATIENTS ---
            const updateMaxPatients = () => {
                const start = rosterStart.value;
                const end = rosterEnd.value;
                
                // Only calculate if times are set
                if (start && end) {
                    rosterMax.value = calculateMaxPatients(start, end);
                } else {
                    rosterMax.value = 1; // Default to 1 if times are not set
                }
            }
            // Attach event listeners to trigger calculation on time change
            rosterStart.onchange = updateMaxPatients;
            rosterEnd.onchange = updateMaxPatients;

            if (shift) {
                rosterActive.checked = true;
                rosterStart.value = shift.time;
                rosterEnd.value = shift.time2;
                rosterMax.value = shift.max_appointment;
                rosterInputs.classList.remove('opacity-50', 'pointer-events-none');
            } else {
                rosterActive.checked = false;
                rosterInputs.classList.add('opacity-50', 'pointer-events-none');
                rosterStart.value = '09:00';
                rosterEnd.value = '17:00';
            }

            // Always run initial calculation when modal opens (after setting times from shift/default)
            updateMaxPatients();

            rosterActive.onchange = function() {
                if(this.checked) {
                    rosterInputs.classList.remove('opacity-50', 'pointer-events-none');
                    updateMaxPatients(); // Recalculate based on current/default times
                } else {
                    rosterInputs.classList.add('opacity-50', 'pointer-events-none');
                }
            };

            modal.classList.remove('hidden');
        }

        function closeModal() { document.getElementById('slotModal').classList.add('hidden'); }
        
        function switchModalTab(tab) {
            document.getElementById('content-block').classList.add('hidden');
            document.getElementById('content-roster').classList.add('hidden');
            
            document.getElementById('tab-block').className = "flex-1 py-3 text-sm font-medium text-slate-500 border-b-2 border-transparent hover:text-red-600 transition focus:outline-none";
            document.getElementById('tab-roster').className = "flex-1 py-3 text-sm font-medium text-slate-500 border-b-2 border-transparent hover:text-green-600 transition focus:outline-none";

            document.getElementById('content-' + tab).classList.remove('hidden');
            document.getElementById('tab-' + tab).className = "flex-1 py-3 text-sm font-bold " + (tab === 'block' ? 'text-red-600 border-red-600' : 'text-green-600 border-green-600');
        }

        function formatTime(t) { if(!t)return''; const [h,m] = t.split(':'); const d=new Date(); d.setHours(h,m); return d.toLocaleTimeString('en-US', {hour:'numeric', minute:'numeric', hour12:true}); }
        
        document.getElementById('prevMonthBtn').onclick = () => { currentDate.setMonth(currentDate.getMonth()-1); renderCalendar(); };
        document.getElementById('nextMonthBtn').onclick = () => { currentDate.setMonth(currentDate.getMonth()+1); renderCalendar(); };
        document.getElementById('todayBtn').onclick = () => { currentDate = new Date(); renderCalendar(); };
        
        // Mobile Sidebar Toggle
        const mobileBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        
        if (mobileBtn && sidebar) {
            mobileBtn.addEventListener('click', () => {
                sidebar.classList.toggle('hidden');
                sidebar.classList.toggle('flex');
                sidebar.classList.toggle('fixed');
                sidebar.classList.toggle('inset-0');
                sidebar.classList.toggle('z-50');
                sidebar.classList.toggle('w-full'); 
            });
        }