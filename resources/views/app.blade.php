<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Translation Management Service</title>
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="tms-app">
            <div class="backdrop"></div>

        <header>
            <div>
                <h1>Translation Management Service</h1>
                <p>Create, tag, and search translations. Export JSON for any locale in a single click.</p>
            </div>
            <div class="status-pill">
                <span id="tokenDot" class="dot"></span>
                <span id="tokenStatus">No token saved</span>
            </div>
        </header>

        <main>
            <section class="stack">
                <div class="card">
                    <h2>Token Access</h2>
                    <form id="authForm">
                        <div class="split">
                            <div>
                                <label for="email">Email</label>
                                <input id="email" type="email" placeholder="admin@example.com" required>
                            </div>
                            <div>
                                <label for="password">Password</label>
                                <input id="password" type="password" placeholder="password" required>
                            </div>
                        </div>
                        <div style="margin-top: 12px;">
                            <label for="deviceName">Device name</label>
                            <input id="deviceName" type="text" placeholder="web-console">
                        </div>
                        <div class="actions">
                            <button type="submit">Get Token</button>
                            <button type="button" id="logoutBtn" class="secondary">Logout</button>
                        </div>
                    </form>
                    <p id="authMessage" class="notice"></p>
                </div>

                <div class="card">
                    <h2>Export JSON</h2>
                    <form id="exportForm">
                        <div class="split">
                            <div>
                                <label for="exportLocale">Locale</label>
                                <input id="exportLocale" type="text" placeholder="en" required>
                            </div>
                            <div>
                                <label for="exportTag">Tag (optional)</label>
                                <input id="exportTag" type="text" placeholder="web">
                            </div>
                        </div>
                        <div class="actions">
                            <button type="submit">Fetch Export</button>
                            <button type="button" id="downloadExport" class="secondary">Download JSON</button>
                        </div>
                    </form>
                    <pre id="exportOutput">{}</pre>
                </div>
            </section>

            <section class="stack">
                <div class="card">
                    <h2>Create Translation</h2>
                    <form id="createForm">
                        <div class="split">
                            <div>
                                <label for="createKey">Key</label>
                                <input id="createKey" type="text" placeholder="auth.login.title" required>
                            </div>
                            <div>
                                <label for="createLocale">Locale</label>
                                <input id="createLocale" type="text" placeholder="en" required>
                            </div>
                        </div>
                        <div style="margin-top: 12px;">
                            <label for="createContent">Content</label>
                            <textarea id="createContent" placeholder="Welcome back" required></textarea>
                        </div>
                        <div style="margin-top: 12px;">
                            <label for="createTags">Tags (comma separated)</label>
                            <input id="createTags" type="text" placeholder="web, mobile">
                        </div>
                        <div class="actions">
                            <button type="submit">Create</button>
                            <button type="reset" class="secondary">Clear</button>
                        </div>
                    </form>
                    <p id="createMessage" class="notice"></p>
                </div>

                <div class="card">
                    <h2>Update Translation</h2>
                    <form id="updateForm">
                        <div class="split">
                            <div>
                                <label for="updateId">Translation ID</label>
                                <input id="updateId" type="text" placeholder="Select a row below" readonly>
                            </div>
                            <div>
                                <label for="updateTags">Tags (comma separated)</label>
                                <input id="updateTags" type="text" placeholder="web, mobile">
                            </div>
                        </div>
                        <div style="margin-top: 12px;">
                            <label for="updateContent">Content</label>
                            <textarea id="updateContent" placeholder="Updated content"></textarea>
                        </div>
                        <div class="actions">
                            <button type="submit">Update</button>
                            <button type="button" id="clearUpdate" class="secondary">Clear</button>
                        </div>
                    </form>
                    <p id="updateMessage" class="notice"></p>
                </div>

                <div class="card translations">
                    <h2>Search &amp; Browse</h2>
                    <form id="searchForm">
                        <div class="split">
                            <div>
                                <label for="searchKey">Key</label>
                                <input id="searchKey" type="text" placeholder="auth.login">
                            </div>
                            <div>
                                <label for="searchContent">Content</label>
                                <input id="searchContent" type="text" placeholder="Welcome">
                            </div>
                            <div>
                                <label for="searchLocale">Locale</label>
                                <input id="searchLocale" type="text" placeholder="en">
                            </div>
                            <div>
                                <label for="searchTag">Tag</label>
                                <input id="searchTag" type="text" placeholder="web">
                            </div>
                        </div>
                        <div class="actions">
                            <button type="submit">Search</button>
                            <button type="button" id="resetSearch" class="secondary">Reset</button>
                        </div>
                    </form>
                    <div style="margin-top: 14px; overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Key</th>
                                    <th>Content</th>
                                    <th>Locale</th>
                                    <th>Tags</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="translationsBody"></tbody>
                        </table>
                    </div>
                    <div class="pagination" style="margin-top: 12px;">
                        <button type="button" id="prevPage" class="secondary">Prev</button>
                        <span id="pageStatus">Page 1</span>
                        <button type="button" id="nextPage" class="secondary">Next</button>
                    </div>
                </div>
            </section>
        </main>
        </div>
    </body>
</html>
