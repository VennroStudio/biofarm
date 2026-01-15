class Albums {
    async generate(elementId, artistId, status) {
        let data = await this.getData(artistId, status);
        document.getElementById(elementId).innerHTML = this.generateTable(data.data);
    }

    async getData(artistId, status) {

        let url = '/v1/stats/albums/conflict?';

        if (artistId) {
            url += 'artistId=' + artistId;
        }

        if (status) {
            url += '&status=' + status;
        }

        return (await fetch(url)).json();
    }

    generateTable(data) {
        let table = '<table class="table">';
        table += '<tr><th>#</th><th>Spotify</th><th>Apple</th><th></th><th>Tidal</th><th>Tracks</th></tr>';

        if (data.length === 0) {
            table += '<tr><td colspan="5" style="text-align: center">no data</td></tr>';
        }

        for (let i = 0; i < data.length; i++) {

            let status = '❌';

            if (data[i].is_approved === 1) {
                if (data[i].is_loaded) {
                    status = '<span title="Loaded">🟢</span>';
                } else if (data[i].all_tracks_mapped) {
                    status = '<span title="Mapped, not loaded">🟡</span>';
                } else {
                    status = '<span title="Conflict with tracks">⚪️</span>';
                }
            }

            let count_style_danger = (null !== data[i].spotify_id && data[i].spotify_total_tracks !== data[i].tidal_total_tracks) || (null !== data[i].apple_id && data[i].apple_total_tracks !== data[i].tidal_total_tracks) ? 'danger' : '';
            let upc_style_danger = (null !== data[i].spotify_id && data[i].spotify_upc !== data[i].tidal_upc) || (null !== data[i].apple_id && data[i].apple_upc !== data[i].tidal_upc) ? 'danger' : '';

            let style = data[i].is_reissued === 1 ? 'style="background: #FFCDD2;"' : '';
            table += '<tr ' + style + '>';
            table += '<td style="text-align: center">' + (i + 1) + '</td>';

            let spotify_is_deleted = data[i].spotify_is_deleted === true ? '<span style="color: red;">[DELETED] </span>' : '';
            let spotify_released_at =  data[i].spotify_released_at !== null ? timeConverter(data[i].spotify_released_at) : '-';
            table += '<td>' +
                '<a href="https://open.spotify.com/album/' + data[i].spotify_id + '" target="_blank">' + spotify_is_deleted +  data[i].spotify_name + '</a>' +
                '<br><span style="color: rgb(40, 40, 40); font-size: 13px;">' + data[i].spotify_artists + '</span>' +
                '<br><span style="color: rgb(80, 80, 80); font-size: 13px;">UPC: <span class="' + upc_style_danger + '">' + (data[i].spotify_upc ?? '-') + '</span></span>' +
                '<div class="small">[' + data[i].id + '] ' + data[i].spotify_type + ' — ' + spotify_released_at + ', tracks: <span class="' + count_style_danger + '">' + data[i].spotify_total_tracks + '</span></div>' +
            '</td>';

            if (null !== data[i].apple_id) {
                let apple_is_deleted = data[i].apple_is_deleted === true ? '<span style="color: red;">[DELETED] </span>' : '';
                let apple_released_at = data[i].apple_released_at !== null ? timeConverter(data[i].apple_released_at) : '-';
                table += '<td>' +
                    '<a href="https://music.apple.com/us/album/' + data[i].apple_id + '" target="_blank">' + apple_is_deleted + data[i].apple_name + '</a>' +
                    '<br><span style="color: rgb(40, 40, 40); font-size: 13px;">' + data[i].apple_artists + '</span>' +
                    '<br><span style="color: rgb(80, 80, 80); font-size: 13px;">UPC: <span class="' + upc_style_danger + '">' + (data[i].apple_upc ?? '-') + '</span></span>' +
                    '<div class="small">[' + data[i].a_id + '] ' + data[i].apple_type + ' — ' + apple_released_at + ', tracks: <span class="' + count_style_danger + '">' + data[i].apple_total_tracks + '</span></div>' +
                    '</td>';
            } else {
                table += '<td style="text-align: center"><span style="color: rgb(210, 210, 210)">—</span></td>';
            }

            table += '<td style="text-align: center; padding: 0 8px 0 8px;">' + status + '</td>';

            let tidal_is_deleted = data[i].tidal_is_deleted === true ? '<span style="color: red;">[DELETED] </span>' : '';
            let tidal_released_at =  data[i].tidal_released_at !== null ? timeConverter(data[i].tidal_released_at) : '-';
            table += '<td>' +
                '<a href="https://listen.tidal.com/album/' + data[i].tidal_id + '" target="_blank">' + tidal_is_deleted + data[i].tidal_name + '</a>' +
                '<br><span style="color: rgb(40, 40, 40); font-size: 13px;">' + data[i].tidal_artists + '</span>' +
                '<br><span style="color: rgb(80, 80, 80); font-size: 13px;">UPC: <span class="' + upc_style_danger + '">' + (data[i].tidal_upc ?? '-') + '</span></span>' +
                '<div class="small">[' + data[i].t_id + '] ' + data[i].tidal_type + ' — ' + tidal_released_at + ', tracks: <span class="' + count_style_danger + '">' + data[i].tidal_total_tracks + '</span></div>' +
            '</td>';

            table += '<td style="text-align: center">' +
                '<a href="/table/tracks?albumId=' + data[i].id + '">' + data[i].merged_tracks + '</a>' +
            '</td>';

            table += '</tr>';
        }
        table += '</table>';
        return table;
    }
}
