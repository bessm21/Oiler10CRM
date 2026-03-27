<div class="header">
    <div>
        <h1>Contacts</h1>
        <p id="contactStats">Manage your contacts</p>
    </div>
    <button class="addBtn" onclick="openModal()">+ Add Contact</button>
</div>

<div class="toolbar">
    <label for="search">Search Contacts</label>
    <input type="text" id="search" placeholder="Search contacts">

    <div class="filters">
        <button onclick="setFilter('All')">All Contacts</button>
        <button onclick="setFilter('Client')">Clients</button>
        <button onclick="setFilter('Lead')">Leads</button>
    </div>
</div>

<div class="content">
    <div id="contactsContainer"></div>

    <div class="contacts-sidebar">
        <div class="card">
            <h3>Overview</h3>
            <div class="stat">
                <p>Total Contacts</p>
                <h2 id="totalContacts">0</h2>
            </div>
            <div class="stat">
                <p>Active Clients</p>
                <h2 id="totalClients">0</h2>
            </div>
            <div class="stat">
                <p>Active Leads</p>
                <h2 id="totalLeads">0</h2>
            </div>
        </div>

        <div class="card">
            <h3>Recent Activity</h3>
            <div id="activity">
                <p>No recent activity</p>
            </div>
        </div>

        <div class="card">
            <h3>Tags</h3>
            <span class="tag client">Client</span>
            <span class="tag lead">Lead</span>
            <span class="tag hot">Hot</span>
            <span class="tag warm">Warm</span>
            <span class="tag cold">Cold</span>
        </div>
    </div>
</div>

<div id="contactModal">
    <div id="contactModalBox">
        <h3 id="modalTitle">Add Contact</h3>
        <input type="hidden" id="contactId">

        <label for="name">Name</label>
        <input id="name" type="text" placeholder="Name">

        <label for="title">Job Title</label>
        <input id="title" type="text" placeholder="Job Title">

        <label for="company">Company</label>
        <input id="company" type="text" placeholder="Company">

        <label for="email">Email</label>
        <input id="email" type="email" placeholder="Email">

        <label for="phone">Phone</label>
        <input id="phone" type="text" placeholder="Phone">

        <label for="type">Contact Type</label>
        <select id="type">
            <option value="Client">Client</option>
            <option value="Lead">Lead</option>
        </select>

        <label for="status">Status</label>
        <select id="status">
            <option value="Hot">Hot</option>
            <option value="Warm">Warm</option>
            <option value="Cold">Cold</option>
        </select>

        <div id="contactModalButtons">
            <button id="saveBtn" onclick="saveContact()">Save Contact</button>
            <button type="button" id="cancelContactBtn" onclick="closeModal()">Cancel</button>
        </div>
    </div>
</div>

<div id="deleteModal" class="delete-modal" style="display: none;">
    <div class="delete-modal-box">
        <h3>Delete Contact</h3>

        <p id="deleteMessage">Are you sure you want to delete this contact?</p>

        <div class="delete-actions">
            <button id="confirmDeleteBtn" onclick="confirmDeleteAction()" class="danger-btn">Yes, Delete</button>
            <button onclick="closeDeleteModal()">No, Cancel</button>
        </div>
    </div>
</div>

<script src="pages/contacts.js"></script>
