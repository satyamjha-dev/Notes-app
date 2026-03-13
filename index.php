<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes App</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

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

                
            

                <button type="submit" id="addBtn" class="btn-add">Add Note</button>
                <div class="dot-loader" id="addLoader">
                    <span></span><span></span><span></span>
                </div>
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
                if (!data.success) { console.error(data.error || 'Failed to load notes.'); return; }

                const grid = $('notesGrid');
                grid.innerHTML = '';
                data.notes.forEach(n => grid.appendChild(buildCard(n)));
                syncEmptyState();
            })
            .catch(() => {
                $('fetchLoader').classList.remove('visible');
                console.error('Network error. Could not load notes.');
            });
    }

   
    $('noteForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const title       = $('title').value.trim();
        const description = $('description').value.trim();

        if (!title || !description) {
            alert('Title and description are required.');
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

                if (!data.success) { console.error(data.error || 'Failed to add note.'); return; }

            
                const grid = $('notesGrid');
                const card = buildCard(data.note);
                grid.prepend(card);
                syncEmptyState();

            
                $('title').value       = '';
                $('description').value = '';
                console.log('Note added');
            })
            .catch(() => {
                $('addLoader').classList.remove('visible');
                btn.disabled = false;
                btn.textContent = 'Add Note';
                console.error('Network error. Could not add note.');
            });
    });

  
    function deleteNote(id, cardEl) {
        cardEl.classList.add('removing');

        const formData = new FormData();
        formData.append('action', 'delete_note');
        formData.append('note_id', id);

        fetch('api.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    cardEl.classList.remove('removing'); 
                    console.error(data.error || 'Failed to delete note.');
                    return;
                }
     
                setTimeout(() => { cardEl.remove(); syncEmptyState(); }, 300);
                console.log('Note deleted');
            })
            .catch(() => {
                cardEl.classList.remove('removing');
                console.error('Network error. Could not delete note.');
            });
    }
    loadNotes();
    </script>
</body>
</html>
