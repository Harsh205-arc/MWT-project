const tbody = document.getElementById('choresTable');

/* Load chores */
fetch('../roomate_app/get_chores.php')
    .then(res => res.json())
    .then(data => {
        tbody.innerHTML = '';

        if (!Array.isArray(data) || data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4">No chores found</td>
                </tr>
            `;
            return;
        }

        data.forEach(chore => {
            const tr = document.createElement('tr');

            tr.innerHTML = `
                <td>${chore.id}</td>
                <td>${chore.title}</td>
                <td>${chore.u_name}</td>
                <td>${chore.completed == 1 ? 'Completed' : 'Pending'}</td>
            `;

            tbody.appendChild(tr);
        });
    })
    .catch(err => {
        console.error(err);
        tbody.innerHTML = `
            <tr>
                <td colspan="4">Error loading chores</td>
            </tr>
        `;
    });

/* Add chore */
document.getElementById('addChoreForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const title = document.getElementById('title').value.trim();
    const assignedTo = document.getElementById('assigned_to').value;

    if (!title || !assignedTo) {
        alert('Fill all fields');
        return;
    }

    fetch('../roomate_app/add_chores.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: `title=${encodeURIComponent(title)}&assigned_to=${encodeURIComponent(assignedTo)}`
    })
    .then(res => res.text())
    .then(() => {
        location.reload();
    })
    .catch(err => console.error(err));
});
