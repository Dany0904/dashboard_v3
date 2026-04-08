define(['jquery'], function($) {

    return {
        init: function() {

            $(document).on('click', '.toggle-detalle', function() {

                const btn = $(this);
                const year = btn.data('year');
                const tr = btn.closest('tr');

                // Toggle si ya existe
                if (tr.next().hasClass('detalle-row')) {
                    tr.next().toggle();
                    return;
                }

                // AJAX
                $.get(M.cfg.wwwroot + '/local/dashboard/ajax/get_courses_by_year.php', {
                    year: year
                }, function(response) {

                    let html = `
                        <tr class="detalle-row">
                            <td colspan="3">
                                <div class="p-3 bg-light rounded">
                                    <strong>Cursos del año ${year}</strong>

                                    <table class="table table-sm mt-2">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Nombre</th>
                                                <th>Fecha</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                    `;

                    if (!response.data || response.data.length === 0) {
                        html += `<tr><td colspan="3">Sin datos</td></tr>`;
                    } else {
                        response.data.forEach(c => {
                            html += `
                                <tr>
                                    <td>${c.id}</td>
                                    <td>${c.fullname}</td>
                                    <td>${c.timecreated}</td>
                                </tr>
                            `;
                        });
                    }

                    html += `
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    `;

                    tr.after(html);
                });

            });

        }
    };
});