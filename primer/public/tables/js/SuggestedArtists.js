class SuggestedArtists {

    async generate(elementId, params) {
        loader(1);
        let data = await this.getData(params);
        document.getElementById(elementId).innerHTML = this.generateTable(data.data.items);
        document.getElementById('total-count').innerHTML = 'Всего: ' + data.data.count;
        document.getElementById('data-order-id').setAttribute('data-order', (params.sort === '0' ? 'desc' : 'asc'));
        loader(0);

        generatePagination(data.data.count, 50, params.offset, 'redirectSuggestedArtists');

        this.initTableSort();
    }

    async getData(params) {

        let url = '/v1/stats/suggested-artists?' + new URLSearchParams(params).toString();

        return (await fetch(url)).json();
    }

    generateTable(data) {

        let table = '<table class="table">';

        table += '<tr>' +
            '<th data-order="asc" id="data-order-id" width="60px;">ID</th>' +
            '<th>Name</th>' +
            '<th width="200px"></th>' +
            '</tr>';

        if (data.length === 0) {
            table += '<tr><td colspan="7" style="text-align: center">no data</td></tr>';
        }

        for (let i = 0; i < data.length; i++) {

            let source = data[i].type === 1 ? 'Spotify' : 'Apple';

            table += '<tr>';
            table += '<td style="text-align: center;">' + data[i].id + '</td>';

            table += '<td>' +
                '<a href="' + data[i].url + '" target="_blank">' + data[i].name.trim() + '</a>' +
                '<div class="small">' +
                'Источник: ' + source + '<br>' +
                '</div>' +
            '</td>';

            table += '<td style="text-align: center;"><button onclick="artists.addArtistModal(\'' +  escape(data[i].name.trim()) + '\', true, \'' +  escape(data[i].url.trim()) + '\')">Добавить артиста</button></td>';

            table += '</tr>';
        }
        table += '</table>';
        return table;
    }

    initTableSort() {
        document.querySelectorAll('th').forEach(th => {
            th.addEventListener('click', () => {

                if (th.getAttribute('id') === 'data-order-id') {
                    const order = th.getAttribute('data-order') || 'asc';

                    if (order === 'asc') {
                        th.setAttribute('data-order', 'desc');
                    } else {
                        th.setAttribute('data-order', 'asc');
                    }

                    redirectSuggestedArtists();
                }
            });
        });
    }

}
