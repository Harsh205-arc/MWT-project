<script>
    fetch('get_chores.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Server error: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (!Array.isArray(data)) {
                throw new Error('Invalid data format');
            }

            const tbody = document.getElementById('choresTable');

            if (data.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4">No chores found</td>
                    </tr>
                `;
                return;
            }

            data.forEach(chore => {
                // field validation
                if (
                    chore.id === undefined ||
                    chore.title === undefined ||
                    chore.u_name === undefined ||
                    chore.completed === undefined
                ) {
                    return; // skip bad row
                }

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
        .catch(error => {
            document.getElementById('choresTable').innerHTML = `
                <tr>
                    <td colspan="4">Error loading chores</td>
                </tr>
            `;
            console.error(error);
        });
</script>
