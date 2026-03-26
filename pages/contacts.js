let contacts = [];
let currentFilter = "All";
let editMode = false;

document.addEventListener("DOMContentLoaded", () => {
    loadContacts();
    document.getElementById("search").addEventListener("input", renderContacts);
});

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

function getInitials(name) {
    let parts = name.trim().split(" ").filter(Boolean);

    if (parts.length === 0) return "?";
    if (parts.length === 1) return parts[0].charAt(0).toUpperCase();

    return (parts[0].charAt(0) + parts[1].charAt(0)).toUpperCase();
}

function saveContact() {
    if (editMode) {
        updateContact();
    } else {
        addContact();
    }
    const name = document.getElementById("name").value;
    const id = document.getElementById("contactId").value;
    if (id) {
        addActivity(`Updated ${name}`);
    } else {
        addActivity(`Added ${name}`);
    }
}

function addContact() {
    let contact = {
        name: document.getElementById("name").value,
        title: document.getElementById("title").value,
        company: document.getElementById("company").value,
        email: document.getElementById("email").value,
        phone: document.getElementById("phone").value,
        type: document.getElementById("type").value,
        status: document.getElementById("status").value
    };

    fetch("pages/add_contacts.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(contact)
    })
        .then(response => response.json())
        .then(data => {
            console.log("add_contacts.php response:", data);

            if (data.success) {
                contacts.push(data);
                updateUI();
                closeModal();
            } else {
                alert("Could not save contact: " + data.message);
            }
        })
        .catch(error => {
            console.error("Add error:", error);
            alert("Something went wrong while saving the contact.");
        });
}

function editContact(id) {
    let contact = contacts.find(c => String(c.id) === String(id));

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

function updateContact() {
    let contact = {
        id: document.getElementById("contactId").value,
        name: document.getElementById("name").value,
        title: document.getElementById("title").value,
        company: document.getElementById("company").value,
        email: document.getElementById("email").value,
        phone: document.getElementById("phone").value,
        type: document.getElementById("type").value,
        status: document.getElementById("status").value
    };

    fetch("pages/update_contact.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(contact)
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                contacts = contacts.map(c =>
                    String(c.id) === String(contact.id) ? contact : c
                );

                updateUI();
                closeModal();
            } else {
                alert("Could not update contact: " + data.message);
            }
        })
        .catch(error => {
            console.error("Update error:", error);
            alert("Something went wrong while updating the contact.");
        });
}

function loadContacts() {
    fetch("pages/get_contacts.php")
        .then(response => response.json())
        .then(data => {
            contacts = data;
            updateUI();
        })
        .catch(error => {
            console.error("Load error:", error);
        });
}

function setFilter(filterType) {
    currentFilter = filterType;
    renderContacts();
}

function updateUI() {
    renderContacts();
    updateStats();
}

function renderContacts() {
    let container = document.getElementById("contactsContainer");
    container.innerHTML = "";

    let searchValue = document.getElementById("search").value.toLowerCase();

    let filteredContacts = contacts.filter(contact => {
        let matchesFilter = currentFilter === "All" || contact.type === currentFilter;

        let matchesSearch =
            contact.name.toLowerCase().includes(searchValue) ||
            contact.company.toLowerCase().includes(searchValue) ||
            contact.email.toLowerCase().includes(searchValue);

        return matchesFilter && matchesSearch;
    });

    filteredContacts.forEach(contact => {
        let initials = getInitials(contact.name);

        let card = `
            <div class="contactCard">
                <div class="contactTop">
                    <div class="avatar">${initials}</div>
                    <div>
                        <h3>${contact.name}</h3>
                        <p>${contact.title}</p>
                    </div>
                </div>

                <p>${contact.company}</p>
                <p>${contact.email}</p>
                <p>${contact.phone}</p>

                <span class="tag ${contact.type.toLowerCase()}">${contact.type}</span>
                <span class="tag ${contact.status.toLowerCase()}">${contact.status}</span>

                <div class="cardActions">
                    <button onclick="editContact(${contact.id})">Edit</button>
                    <button onclick="openDeleteModal(${contact.id})">Delete</button>
                </div>
            </div>
        `;

        container.innerHTML += card;
    });
}

function updateStats() {
    let total = contacts.length;
    let clients = contacts.filter(c => c.type === "Client").length;
    let leads = contacts.filter(c => c.type === "Lead").length;

    document.getElementById("totalContacts").textContent = String(total);
    document.getElementById("totalClients").textContent = String(clients);
    document.getElementById("totalLeads").textContent = String(leads);

    document.getElementById("contactStats").textContent =
        `${total} Contact${total !== 1 ? "s" : ""} • ` +
        `${clients} Client${clients !== 1 ? "s" : ""} • ` +
        `${leads} Lead${leads !== 1 ? "s" : ""}`;
}

function deleteContact(id) {
    fetch("pages/delete_contact.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({ id: id })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                contacts = contacts.filter(contact => String(contact.id) !== String(id));
                updateUI();
            } else {
                alert("Could not delete contact: " + data.message);
            }
        })
        .catch(error => {
            console.error("Delete error:", error);
            alert("Something went wrong while deleting the contact.");
        });
    const contact = contacts.find(c => c.id == id);
    addActivity(`Deleted ${contact.name}`);
}

function getActivities() {
    return JSON.parse(localStorage.getItem("contactActivities")) || [];
}

function saveActivities(activities) {
    localStorage.setItem("contactActivities", JSON.stringify(activities));
}

function formatActivityDate(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();

    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const activityDay = new Date(date.getFullYear(), date.getMonth(), date.getDate());

    const diffTime = today - activityDay;
    const diffDays = diffTime / (1000 * 60 * 60 * 24);

    if (diffDays === 0) {
        return date.toLocaleTimeString([], {
            hour: "2-digit",
            minute: "2-digit"
        });
    } else if (diffDays === 1) {
        return "Yesterday";
    } else {
        return date.toLocaleDateString();
    }
}

function renderActivities() {
    const activityDiv = document.getElementById("activity");
    const activities = getActivities();

    activityDiv.innerHTML = "";

    if (activities.length === 0) {
        activityDiv.innerHTML = "<p>No recent activity</p>";
        return;
    }

    activities.forEach(activity => {
        const p = document.createElement("p");
        const displayTime = formatActivityDate(activity.timestamp);
        p.textContent = `${activity.text} (${displayTime})`;
        activityDiv.appendChild(p);
    });
}

function addActivity(message) {
    const activities = getActivities();

    activities.unshift({
        text: message,
        timestamp: new Date().toISOString()
    });

    if (activities.length > 3) {
        activities.pop();
    }

    saveActivities(activities);
    renderActivities();
}


let contactToDelete = null;

function openDeleteModal(id) {
    const contact = contacts.find(c => String(c.id) === String(id));
    if (!contact) return;

    contactToDelete = id;

    document.getElementById("deleteMessage").textContent =
        `Are you sure you want to delete ${contact.name}?`;

    document.getElementById("deleteModal").style.display = "flex";
}

function closeDeleteModal() {
    document.getElementById("deleteModal").style.display = "none";
    contactToDelete = null;
}

document.addEventListener("DOMContentLoaded", function () {
    renderActivities();

    const confirmBtn = document.getElementById("confirmDeleteBtn");
    if (confirmBtn) {
        confirmBtn.addEventListener("click", function () {
            if (contactToDelete !== null) {
                deleteContact(contactToDelete);
                closeDeleteModal();
            }
        });
    }
});