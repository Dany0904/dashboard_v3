define(['jquery'], function($) {

    return {
        init: function() {

            $(document).on('click', '.toggle-detalle-cat', function() {

                const btn = $(this);
                const id = btn.data('id');
                const name = btn.data('name');
                const tr = btn.closest('tr');

                // Toggle
                if (tr.next().hasClass('detalle-row')) {
                    tr.next().toggle();
                    return;
                }

                $.get(M.cfg.wwwroot + '/local/dashboard/ajax/get_category_detail.php', {
                    idcategory: id
                }, function(response) {

                    let html = `
                        <tr class="detalle-row">
                            <td colspan="4">
                                <div class="p-3 bg-light rounded">
                                    <strong>Cursos en: ${name}</strong>

                                    <table class="table table-sm mt-2">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Nombre</th>
                                                <th>Inicio</th>
                                                <th>Fin</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                    `;

                    if (!response.data || response.data.length === 0) {
                        html += `<tr><td colspan="5">Sin datos</td></tr>`;
                    } else {
                        response.data.forEach(c => {
                            html += `
                                <tr>
                                    <td>${c.id}</td>
                                    <td>${c.fullname}</td>
                                    <td>${c.startdate || '-'}</td>
                                    <td>${c.enddate || '-'}</td>
                                    <td>
                                        <a href="${M.cfg.wwwroot}/course/edit.php?id=${c.id}" 
                                           target="_blank" 
                                           class="btn btn-sm btn-info">
                                            Configurar
                                        </a>
                                    </td>
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