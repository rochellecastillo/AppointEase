if (typeof lucide !== 'undefined') lucide.createIcons();
        
        // --- CONSTANTS ---
        const APPOINTMENT_DURATION_MINUTES = 30; // 30 minutes per patient slot
        const daysOfWeek = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        let currentDate = new Date();
        let rosterData = [];
        let leaveData = [];
        
        const doctorSelector = document.getElementById('doctorSelector');
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
            // Check for valid time formats (optional, but good practice)
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

        // Init
        document.addEventListener('DOMContentLoaded', () => {
            if (doctorSelector) {
                // Fetch data immediately for the doctor set by PHP
                fetchData();
                
                // FIX: When dropdown changes, redirect so the URL matches the view
                doctorSelector.addEventListener('change', function() {
                    const selectedId = this.value;
                    window.location.search = '?doctor_id=' + selectedId;
                });
            }
        });

        async function fetchData() {
            const docId = doctorSelector.value;
            if (!docId) return;

            monthDisplay.textContent = "Loading...";
            try {
                // Ensure we pass target_id to AJAX so it matches the dropdown
                const response = await fetch(`?action=get_calendar_data&target_id=${docId}`);
                const data = await response.json();
                
                if (data.status === 'error') throw new Error(data.message);
                
                rosterData = data.roster || [];
                leaveData = data.leaves || [];
                renderCalendar();
            } catch (error) {
                grid.innerHTML = `<div class="col-span-7 py-12 text-center text-red-500">Error: ${error.message}</div>`;
            }
        }

        function renderCalendar() {
            grid.innerHTML = '';
            ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'].forEach(d => 
                grid.innerHTML += `<div class="day-header">${d}</div>`
            );

            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            monthDisplay.textContent = new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' }).format(currentDate);

            const firstDayIndex = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const today = new Date();
            today.setHours(0,0,0,0);

            // Empty cells for prev month
            for (let i = 0; i < firstDayIndex; i++) {
                const cell = document.createElement('div');
                cell.className = 'day-cell other-month';
                grid.appendChild(cell);
            }

            for (let d = 1; d <= daysInMonth; d++) {
                const dateObj = new Date(year, month, d);
                const dateStr = [
                    dateObj.getFullYear(), 
                    String(dateObj.getMonth() + 1).padStart(2, '0'), 
                    String(dateObj.getDate()).padStart(2, '0')
                ].join('-');
                
                const dayIndex = dateObj.getDay(); 
                
                // Important: Match DB Logic. (JS 0=Sun, 1=Mon...6=Sat) to (DB 1=Mon, 7=Sun)
                let dbDay = (dayIndex === 0) ? 7 : dayIndex; 
                const shift = rosterData.find(r => parseInt(r.day) === dbDay);

                const isPast = dateObj < today;
                const leave = leaveData.find(l => dateStr >= l.date_start && dateStr <= l.date_end);
                
                const cell = document.createElement('div');
                cell.className = 'day-cell';
                if (isPast) cell.classList.add('day-past');

                let html = `<span class="text-sm font-semibold text-gray-700">${d}</span>`;

                if (leave) {
                    html += `<div class="event-chip event-block">⛔ ${leave.reason}</div>`;
                    cell.style.background = '#fff1f2';
                } else if (shift) {
                    html += `<div class="event-chip event-roster">${formatTime(shift.time)}-${formatTime(shift.time2)}</div>`;
                }

                cell.innerHTML = html;

                if (!isPast) {
                    cell.onclick = () => openModal(dateStr, dbDay, dayIndex, shift, leave);
                }

                grid.appendChild(cell);
            }
        }

        function openModal(dateStr, dbDay, jsDayIndex, shift, leave) {
            const modal = document.getElementById('slotModal');
            const dateObj = new Date(dateStr);
            
            document.getElementById('modalDateDisplay').textContent = dateObj.toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' });
            document.getElementById('rosterDayName').textContent = daysOfWeek[jsDayIndex];

            // Set Hidden Inputs
            document.getElementById('formBlockDate').value = dateStr;
            document.getElementById('formUnblockDate').value = dateStr;
            document.getElementById('formRosterDayIndex').value = dbDay; 
            
            const docId = doctorSelector.value;
            if(document.getElementById('formBlockDocId')) document.getElementById('formBlockDocId').value = docId;
            if(document.getElementById('formUnblockDocId')) document.getElementById('formUnblockDocId').value = docId;
            if(document.getElementById('formRosterDocId')) document.getElementById('formRosterDocId').value = docId;

            // Toggle Block/Unblock UI
            if (leave) {
                document.getElementById('blockForm').classList.add('hidden');
                document.getElementById('unblockForm').classList.remove('hidden');
                document.getElementById('blockReasonDisplay').textContent = `Reason: ${leave.reason}`;
                switchModalTab('block');
            } else {
                document.getElementById('unblockForm').classList.add('hidden');
                document.getElementById('blockForm').classList.remove('hidden');
                switchModalTab('roster'); 
            }

            // Setup Roster Form
            const rosterInputs = document.getElementById('rosterInputs');
            const rosterActive = document.getElementById('rosterActive');
            const rosterStart = document.getElementById('rosterStart');
            const rosterEnd = document.getElementById('rosterEnd');
            const rosterMax = document.getElementById('rosterMax');

            // Function to calculate and update Max Patients based on Start/End times
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
                rosterInputs.classList.remove('opacity-50', 'pointer-events-none');
            } else {
                rosterActive.checked = false;
                rosterInputs.classList.add('opacity-50', 'pointer-events-none');
                // Set default times if inactive
                rosterStart.value = '08:00';
                rosterEnd.value = '17:00';
            }

            // Always run initial calculation when modal opens (after setting times from shift/default)
            updateMaxPatients();

            // Toggle activation logic
            rosterActive.onclick = function() {
                if(this.checked) {
                    rosterInputs.classList.remove('opacity-50', 'pointer-events-none');
                    updateMaxPatients(); // Recalculate based on current/default times
                }
                else {
                    rosterInputs.classList.add('opacity-50', 'pointer-events-none');
                }
            };

            modal.classList.remove('hidden');
        }

        function closeModal() { document.getElementById('slotModal').classList.add('hidden'); }

        function switchModalTab(tab) {
            const blockContent = document.getElementById('content-block');
            const rosterContent = document.getElementById('content-roster');
            const tabBlock = document.getElementById('tab-block');
            const tabRoster = document.getElementById('tab-roster');

            if (tab === 'block') {
                blockContent.classList.remove('hidden');
                rosterContent.classList.add('hidden');
                
                tabBlock.className = "flex-1 py-2 text-sm font-bold text-red-600 border-b-2 border-red-600 focus:outline-none transition";
                tabRoster.className = "flex-1 py-2 text-sm font-medium text-gray-500 border-b-2 border-transparent hover:text-purple-600 focus:outline-none transition";
            } else {
                blockContent.classList.add('hidden');
                rosterContent.classList.remove('hidden');

                tabBlock.className = "flex-1 py-2 text-sm font-medium text-gray-500 border-b-2 border-transparent hover:text-red-600 focus:outline-none transition";
                tabRoster.className = "flex-1 py-2 text-sm font-bold text-purple-600 border-b-2 border-purple-600 transition";
            }
        }

        function formatTime(t) { 
            if(!t) return ''; 
            const [h, m] = t.split(':'); 
            const d = new Date(); 
            d.setHours(h, m); 
            return d.toLocaleTimeString('en-US', {hour:'numeric', minute:'numeric', hour12:true}); 
        }
        
        document.getElementById('prevMonth').onclick = () => { currentDate.setMonth(currentDate.getMonth()-1); fetchData(); };
        document.getElementById('nextMonth').onclick = () => { currentDate.setMonth(currentDate.getMonth()+1); fetchData(); };
        document.getElementById('todayBtn').onclick = () => { currentDate = new Date(); fetchData(); };