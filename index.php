<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes App</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .dot-loader {
            display: none;        
            justify-content: center;
            align-items: center;
            gap: 6px;
            padding: 1rem 0;
        }
        .dot-loader.visible { display: flex; }

        .dot-loader span {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background-color: #555;
            animation: bounce 1.2s infinite ease-in-out;
        }
        .dot-loader span:nth-child(1) { animation-delay: 0s;    }
        .dot-loader span:nth-child(2) { animation-delay: 0.2s;  }
        .dot-loader span:nth-child(3) { animation-delay: 0.4s;  }

        @keyframes bounce {
            0%, 80%, 100% { transform: scale(0.6); opacity: 0.4; }
            40%            { transform: scale(1.1); opacity: 1;   }
        }


        #toast {
            position: fixed;
            bottom: 1.5rem;
            left: 50%;
            transform: translateX(-50%) translateY(80px);
            background: #222;
            color: #fff;
            padding: 0.75rem 1.5rem;
            border-radius: 999px;
            font-size: 0.9rem;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: transform 0.3s ease, opacity 0.3s ease;
            opacity: 0;
            z-index: 1000;
            white-space: nowrap;
        }
        #toast.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }
        #toast.error { background: #c0392b; }

    
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(12px); }
            to   { opacity: 1; transform: translateY(0);    }
        }
        .note-card { animation: cardIn 0.35s ease forwards; }

        
        .note-card.removing {
            animation: cardOut 0.3s ease forwards;
        }
        @keyframes cardOut {
            to { opacity: 0; transform: scale(0.9); }
        }

        .btn-add:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div id="toast"></div>

    <div class="container">
        <header class="app-header">
            <h1>Notes App</h1>
            <p>A simple and elegant way to keep your thoughts organized.</p>
        </header>

 
        <div class="form-container">
            <form id="noteForm" class="note-form" novalidate>
                <div class="input-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" placeholder="Add a title here" required>
                </div>

                <div class="input-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" placeholder="Write your note here..." rows="4" required></textarea>
                </div>

                <!-- Dot loader shown while adding -->
                <div class="dot-loader" id="addLoader">
                    <span></span><span></span><span></span>
                </div>

                <button type="submit" id="addBtn" class="btn-add">Add Note</button>
            </form>
        </div>

        <div class="notes-section">
            <h2>All Notes</h2>

            <div class="dot-loader visible" id="fetchLoader">
                <span></span><span></span><span></span>
            </div>

            <div class="notes-grid" id="notesGrid"></div>

            <div class="empty-state" id="emptyState" style="display:none;">
                <p>No notes found. Add a note to get started!</p>
            </div>
        </div>
    </div>

    <script>

    const $ = id => document.getElementById(id);

    let toastTimer;
    function showToast(msg, isError = false) {
        const t = $('toast');
        t.textContent = msg;
        t.className   = 'show' + (isError ? ' error' : '');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => { t.className = ''; }, 2800);
    }

 
    function buildCard(note) {
        const card = document.createElement('div');
        card.className = 'note-card';
        card.dataset.id = note.id;
        card.innerHTML = `
            <div class="note-content">
                <h3>${escHtml(note.title)}</h3>
                <p>${escHtml(note.description)}</p>
            </div>
            <div class="delete-form">
                <button class="btn-delete" data-id="${note.id}">Delete</button>
            </div>`;

        card.querySelector('.btn-delete').addEventListener('click', () => deleteNote(note.id, card));
        return card;
    }

    function escHtml(str) {
        return str
            .replace(/&/g,'&amp;')
            .replace(/</g,'&lt;')
            .replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;');
    }

   
    function syncEmptyState() {
        const grid  = $('notesGrid');
        const empty = $('emptyState');
        const hasCards = grid.children.length > 0;
        empty.style.display = hasCards ? 'none' : 'block';
    }

  
    function loadNotes() {
        $('fetchLoader').classList.add('visible');

        fetch('api.php?action=get_notes')
            .then(r => r.json())
            .then(data => {
                $('fetchLoader').classList.remove('visible');
                if (!data.success) { showToast(data.error || 'Failed to load notes.', true); return; }

                const grid = $('notesGrid');
                grid.innerHTML = '';
                data.notes.forEach(n => grid.appendChild(buildCard(n)));
                syncEmptyState();
            })
            .catch(() => {
                $('fetchLoader').classList.remove('visible');
                showToast('Network error. Could not load notes.', true);
            });
    }

   
    $('noteForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const title       = $('title').value.trim();
        const description = $('description').value.trim();

        if (!title || !description) {
            showToast('Title and description are required.', true);
            return;
        }

       
        $('addLoader').classList.add('visible');
        const btn = $('addBtn');
        btn.disabled = true;
        btn.textContent = 'Adding…';

        const formData = new FormData();
        formData.append('action', 'add_note');
        formData.append('title', title);
        formData.append('description', description);

        fetch('api.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                $('addLoader').classList.remove('visible');
                btn.disabled = false;
                btn.textContent = 'Add Note';

                if (!data.success) { showToast(data.error || 'Failed to add note.', true); return; }

            
                const grid = $('notesGrid');
                const card = buildCard(data.note);
                grid.prepend(card);
                syncEmptyState();

                // Clear form
                $('title').value       = '';
                $('description').value = '';
                showToast('Note added!');
            })
            .catch(() => {
                $('addLoader').classList.remove('visible');
                btn.disabled = false;
                btn.textContent = 'Add Note';
                showToast('Network error. Could not add note.', true);
            });
    });

  
    function deleteNote(id, cardEl) {
        // Animate card out first
        cardEl.classList.add('removing');

        const formData = new FormData();
        formData.append('action', 'delete_note');
        formData.append('note_id', id);

        fetch('api.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    cardEl.classList.remove('removing'); // restore card
                    showToast(data.error || 'Failed to delete note.', true);
                    return;
                }
     
                setTimeout(() => { cardEl.remove(); syncEmptyState(); }, 300);
                showToast('Note deleted.');
            })
            .catch(() => {
                cardEl.classList.remove('removing');
                showToast('Network error. Could not delete note.', true);
            });
    }

  
    loadNotes();
    </script>
</body>
</html>
