let contacts = [];
let currentFilter = "All";
let editMode = false;
let pendingDeleteId = null;

// --- INITIALIZATION ---
document.addEventListener("DOMContentLoaded", () => {
    loadContacts();
    renderActivities();

    const searchInput = document.getElementById("search");
    if (searchInput) {
        searchInput.addEventListener("input", renderContacts);
    }
});

// --- CORE DATA FETCHING ---
function loadContacts() {
    fetch("pages/get_contacts.php")
        .then(response => response.json())
        .then(data => {
            contacts = Array.isArray(data) ? data : [];
            updateUI();
        })
        .catch(error => console.error("Load error:", error));
}

// --- MODAL MANAGEMENT ---
function openModal() {
    document.getElementById("contactModal").style.display = "block";
}

function closeModal() {
    document.getElementById("contactModal").style.display = "none";
    clearForm();
}

function clearForm() {
    document.getElementById("contactId").value = "";
    document.getElementById("name").value = "";
    document.getElementById("title").value = "";
    document.getElementById("company").value = "";
    document.getElementById("email").value = "";
    document.getElementById("phone").value = "";
    document.getElementById("type").value = "Client";
    document.getElementById("status").value = "Hot";
    document.getElementById("modalTitle").textContent = "Add Contact";
    document.getElementById("saveBtn").textContent = "Save Contact";
    editMode = false;
}

// --- SAVE / UPDATE LOGIC ---
function saveContact() {
    const contactData = {
        id: document.getElementById("contactId").value,
        name: document.getElementById("name").value,
        title: document.getElementById("title").value,
        company: document.getElementById("company").value,
        email: document.getElementById("email").value,
        phone: document.getElementById("phone").value,
        type: document.getElementById("type").value,
        status: document.getElementById("status").value
    };

    const url = editMode ? "pages/update_contact.php" : "pages/add_contacts.php";
    const isEdit = editMode;

    let tempId = null;

    if (isEdit) {
        const idx = contacts.findIndex(c => String(c.id) === String(contactData.id));
        if (idx !== -1) contacts[idx] = { ...contacts[idx], ...contactData };
        addActivity(`Updated ${contactData.name}`);
    } else {
        tempId = "tmp_" + Date.now();
        contacts.push({ ...contactData, id: tempId });
        addActivity(`Added ${contactData.name}`);
    }

    updateUI();
    closeModal();

    fetch(url, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(contactData)
    })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (!isEdit && data.id && tempId) {
                    const idx = contacts.findIndex(c => c.id === tempId);
                    if (idx !== -1) contacts[idx].id = data.id;
                }
            } else {
                alert("Error saving contact: " + data.message);
                loadContacts();
            }
        })
        .catch(err => { console.error("Save error:", err); loadContacts(); });
}

function editContact(id) {
    const contact = contacts.find(c => String(c.id) === String(id));
    if (!contact) return;

    document.getElementById("contactId").value = contact.id;
    document.getElementById("name").value = contact.name;
    document.getElementById("title").value = contact.title;
    document.getElementById("company").value = contact.company;
    document.getElementById("email").value = contact.email;
    document.getElementById("phone").value = contact.phone;
    document.getElementById("type").value = contact.type;
    document.getElementById("status").value = contact.status;

    document.getElementById("modalTitle").textContent = "Edit Contact";
    document.getElementById("saveBtn").textContent = "Update Contact";

    editMode = true;
    openModal();
}

// --- DELETE LOGIC ---
function openDeleteModal(id, name) {
    if (!id || id === "undefined") {
        alert("CRITICAL: The card did not pass an ID.");
        return;
    }

    pendingDeleteId = id;

    document.getElementById("deleteMessage").textContent =
        `Are you sure you want to delete ${name}?`;

    document.getElementById("deleteModal").style.display = "flex";
}

function closeDeleteModal() {
    pendingDeleteId = null;
    document.getElementById("deleteModal").style.display = "none";
}

function confirmDeleteAction() {
    if (!pendingDeleteId) {
        alert("Error: No contact selected for deletion.");
        return;
    }

    const idToDelete = pendingDeleteId;

    // Optimistic: remove from local array and re-render immediately
    contacts = contacts.filter(c => String(c.id) !== String(idToDelete));
    addActivity("Deleted a contact");
    updateUI();
    closeDeleteModal();

    // Fire delete in background
    fetch("pages/delete_contact.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id: idToDelete })
    })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert("Database failed to delete: " + data.message);
                loadContacts(); // re-sync if it failed
            }
        })
        .catch(err => console.error("Server error:", err));
}

// --- UI RENDERING ---
function setFilter(filterType) {
    currentFilter = filterType;
    renderContacts();
}

function updateUI() {
    renderContacts();
    updateStats();
}

function renderContacts() {
    const container = document.getElementById("contactsContainer");
    if (!container) return;

    container.innerHTML = "";
    const searchValue = document.getElementById("search").value.toLowerCase();

    const filtered = contacts.filter(c => {
        const matchesFilter = currentFilter === "All" || c.type === currentFilter;
        const matchesSearch =
            c.name.toLowerCase().includes(searchValue) ||
            c.company.toLowerCase().includes(searchValue);
        return matchesFilter && matchesSearch;
    });

    filtered.forEach(c => {
        const initials = getInitials(c.name || "Unknown");

        const card = document.createElement("div");
        card.className = "contactCard";
        card.innerHTML = `
            <div class="card-header-row">
                <div class="contactTop">
                    <div class="avatar">${initials}</div>
                    <div class="contactInfo">
                        <h3>${c.name}</h3>
                        <p class="contactTitle">${c.title || ""}</p>
                    </div>
                </div>
                <div class="card-menu-wrap">
                    <button class="menuTrigger" aria-label="Actions">&#8942;</button>
                    <div class="cardDropdown">
                        <button class="editBtn">Edit</button>
                        <button class="deleteBtn delete-red">Delete</button>
                    </div>
                </div>
            </div>
            <div class="contactDetail">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                <span>${c.company || "&mdash;"}</span>
            </div>
            ${(c.email || c.phone) ? `<div class="contactDetailRow">
                ${c.email ? `<a href="mailto:${c.email}" class="contact-link contact-chip">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                    ${c.email}</a>` : ""}
                ${c.phone ? `<a href="tel:${c.phone}" class="contact-link contact-chip">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.9 15.21 19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    ${c.phone}</a>` : ""}
            </div>` : ""}
            <div class="contactTags">
                <span class="tag ${(c.type || "Client").toLowerCase()}">${c.type || "Client"}</span>
                <span class="tag ${(c.status || "Hot").toLowerCase()}">${c.status || "Hot"}</span>
            </div>`;

        card.querySelector(".editBtn").addEventListener("click", () => editContact(c.id));
        card.querySelector(".deleteBtn").addEventListener("click", () => openDeleteModal(c.id, c.name));

        container.appendChild(card);
    });
}

function updateStats() {
    const total = contacts.length;
    const clients = contacts.filter(c => c.type === "Client").length;
    const leads = contacts.filter(c => c.type === "Lead").length;

    document.getElementById("totalContacts").textContent = String(total);
    document.getElementById("totalClients").textContent = String(clients);
    document.getElementById("totalLeads").textContent = String(leads);

    document.getElementById("contactStats").textContent =
        `${total} Contact${total !== 1 ? "s" : ""} • ${clients} Client${clients !== 1 ? "s" : ""} • ${leads} Lead${leads !== 1 ? "s" : ""}`;
}

function getInitials(name) {
    const parts = name.trim().split(" ").filter(Boolean);
    if (parts.length === 0) return "?";
    if (parts.length === 1) return parts[0].charAt(0).toUpperCase();
    return (parts[0].charAt(0) + parts[1].charAt(0)).toUpperCase();
}

// --- ACTIVITY LOGGING (LocalStorage) ---
function addActivity(message) {
    const activities = JSON.parse(localStorage.getItem("contactActivities")) || [];
    activities.unshift({ text: message, timestamp: new Date().toISOString() });
    if (activities.length > 5) activities.pop();
    localStorage.setItem("contactActivities", JSON.stringify(activities));
    renderActivities();
}

function renderActivities() {
    const activityDiv = document.getElementById("activity");
    if (!activityDiv) return;

    const activities = JSON.parse(localStorage.getItem("contactActivities")) || [];
    activityDiv.innerHTML = activities.length ? "" : "<p>No recent activity</p>";

    activities.forEach(a => {
        const p = document.createElement("p");
        const time = new Date(a.timestamp).toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
        p.textContent = `${a.text} (${time})`;
        activityDiv.appendChild(p);
    });
}