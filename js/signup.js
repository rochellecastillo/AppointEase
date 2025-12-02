// signup.js (IMPROVED VERSION)

// Initialize Lucide icons
if (window.lucide) lucide.createIcons();

document.addEventListener("DOMContentLoaded", () => {

    const form = document.querySelector('form[action=""]');

    // Elements
    const passwordField = $("#password");
    const confirmField = $("#confirm_password");
    const contactField = $('[name="contact"]');
    const bdateField = $('[name="bdate"]');
    const usernameField = $('[name="user_name"]');
    const submitBtn = form.querySelector('button[type="submit"]');

    let submitting = false;

    /* -----------------------------
     * Utility Functions
     * ----------------------------- */

    function $(selector, parent = document) {
        return parent.querySelector(selector);
    }

    function showIcons() {
        if (window.lucide) lucide.createIcons();
    }

    const normalizePhonePH = (value) => {
        let v = value.replace(/[\s\-\(\)\.]/g, "").trim();

        if (v.startsWith("+63")) v = "0" + v.slice(3);
        if (v.startsWith("63")) v = "0" + v.slice(2);
        if (/^9\d{9}$/.test(v)) v = "0" + v;

        return v;
    };

    const isValidPhonePH = (v) => /^09\d{9}$/.test(normalizePhonePH(v));

    const passwordChecks = (pw) => ({
        length: pw.length >= 8,
        upper: /[A-Z]/.test(pw),
        lower: /[a-z]/.test(pw),
        number: /[0-9]/.test(pw),
        special: /[^A-Za-z0-9]/.test(pw)
    });

    const scorePassword = (pw) => {
        const c = passwordChecks(pw);
        return Object.values(c).filter(Boolean).length;
    };

    function togglePassword(id) {
        const field = $("#" + id);
        const icon = $("#eye-" + id);

        const isHidden = field.type === "password";
        field.type = isHidden ? "text" : "password";
        icon.setAttribute("data-lucide", isHidden ? "eye-off" : "eye");
        showIcons();
    }

    window.togglePassword = togglePassword;

    /* -----------------------------
     * Password Strength Meter
     * ----------------------------- */
    const updatePasswordUI = () => {
        const pw = passwordField.value;
        const s = scorePassword(pw);

        const bar = $("#pwBarFill");
        const label = $("#pwScoreLabel");

        if (!bar || !label) return;

        const states = [
            { w: "20%", c: "#ef4444", t: "Very weak" },
            { w: "40%", c: "#f59e0b", t: "Weak" },
            { w: "60%", c: "#fbbf24", t: "Fair" },
            { w: "80%", c: "#10b981", t: "Good" },
            { w: "100%", c: "#059669", t: "Strong" }
        ];

        const state = states[Math.min(s - 1, 4)] || states[0];

        bar.style.width = state.w;
        bar.style.background = state.c;
        label.textContent = state.t;

        // Checklist highlights
        const checks = passwordChecks(pw);
        ["length", "upper", "lower", "number"].forEach((key) => {
            const el = $("#chk-" + key);
            if (el) el.style.opacity = checks[key] ? "1" : "0.45";
        });
    };

    /* -----------------------------
     * Confirm Password Validation
     * ----------------------------- */
    const updateConfirmUI = () => {
        const msg = $("#confirmMsg");
        if (!msg) return;

        const match = confirmField.value === passwordField.value;

        if (!confirmField.value) {
            msg.textContent = "Must match password";
            msg.className = "text-xs text-gray-500";
        } else if (match) {
            msg.textContent = "Passwords match";
            msg.className = "text-xs text-green-600";
        } else {
            msg.textContent = "Passwords do not match";
            msg.className = "text-xs text-red-600";
        }
    };

    /* -----------------------------
     * Client-side Validation
     * ----------------------------- */
    function showClientErrors(errors) {
        let box = $(".client-errors");

        if (!box) {
            box = document.createElement("div");
            box.className = "client-errors mb-6";
            form.parentElement.insertBefore(box, form);
        }

        box.className = "client-errors bg-red-50 border-l-4 border-red-500 p-4 mb-6";
        box.innerHTML = `
            <div class="flex">
                <i data-lucide="alert-circle" class="w-5 h-5 text-red-500"></i>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Please fix the following issues:</h3>
                    <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                        ${errors.map(err => `<li>${err}</li>`).join("")}
                    </ul>
                </div>
            </div>`;
        
        showIcons();
    }

    function validateForm() {
        const errors = [];

        // Required fields
        const required = [
            ["first_name", "First Name"],
            ["last_name", "Last Name"],
            ["bdate", "Birth Date"],
            ["gender", "Gender"],
            ["address", "Address"],
            ["contact", "Contact Number"],
            ["user_name", "Username"],
            ["password", "Password"],
            ["confirm_password", "Confirm Password"]
        ];

        required.forEach(([name, label]) => {
            if (!$(`[name="${name}"]`).value.trim()) {
                errors.push(`${label} is required.`);
            }
        });

        const pw = passwordField.value;
        const pwCheck = passwordChecks(pw);

        if (!pwCheck.upper || !pwCheck.lower || !pwCheck.number) {
            errors.push("Password must include uppercase, lowercase, and number.");
        }

        if (pw !== confirmField.value) {
            errors.push("Passwords do not match.");
        }

        const contact = normalizePhonePH(contactField.value);
        contactField.value = contact;

        if (!isValidPhonePH(contact)) {
            errors.push("Invalid Philippine mobile number.");
        }

        // Age check
        const age = new Date().getFullYear() - new Date(bdateField.value).getFullYear();
        if (age < 13) {
            errors.push("You must be at least 13 years old.");
        }

        // Terms
        if (!$('[name="terms_accepted"]').checked) {
            errors.push("You must accept the Terms and Privacy Policy.");
        }

        return errors;
    }

    /* -----------------------------
     * Form Submit Handler
     * ----------------------------- */
    form.addEventListener("submit", (e) => {
        const errors = validateForm();

        if (errors.length > 0) {
            e.preventDefault();
            showClientErrors(errors);
            return;
        }

        if (submitting) {
            e.preventDefault();
            return;
        }

        submitting = true;

        // Add spinner
        submitBtn.disabled = true;
        submitBtn.insertAdjacentHTML(
            "beforeend",
            '<span class="ml-2 animate-spin"><svg class="w-4 h-4" viewBox="0 0 50 50"><circle cx="25" cy="25" r="20" stroke="white" stroke-width="5" fill="none"></circle></svg></span>'
        );
    });

    /* -----------------------------
     * Live Updates
     * ----------------------------- */
    passwordField.addEventListener("input", updatePasswordUI);
    confirmField.addEventListener("input", updateConfirmUI);

    contactField.addEventListener("blur", () => {
        contactField.value = normalizePhonePH(contactField.value);
    });

});