class Tracks {

    async generate(elementId, albumId, status) {
        let data = await this.getData(albumId, status);
        document.getElementById(elementId).innerHTML = this.generateTable(data.data);
    }

    async getData(albumId, status) {

        let url = '/v1/stats/tracks/conflict?';

        if (albumId) {
            url += 'albumId=' + albumId;
        }

        if (status) {
            url += '&status=' + status;
        }

        return (await fetch(url)).json();
    }

    generateTable(data) {
        let table = '<table class="table">';
        table += '<tr><th>#</th><th>Spotify</th><th>Apple</th><th></th><th>Tidal</th></tr>';

        if (data.length === 0) {
            table += '<tr><td colspan="5" style="text-align: center">no data</td></tr>';
        }

        for (let i = 0; i < data.length; i++) {

            let style = data[i].is_reissued === 1 ? 'style="background: #FFCDD2;"' : '';
            table += '<tr ' + style + '>';
            table += '<td style="text-align: center">' + (i + 1) + '</td>';

            let spotify_is_deleted = data[i].spotify_is_deleted === true ? '<span style="color: red;">[DELETED] </span>' : '';
            table += '<td>' +
                '<a href="https://open.spotify.com/track/' + data[i].spotify_id + '" target="_blank">' + spotify_is_deleted + data[i].spotify_name + '</a>' +
                '<br><span style="color: rgb(40, 40, 40); font-size: 13px;">' + data[i].spotify_artists + '</span>' +
                '<br><span style="color: rgb(80, 80, 80); font-size: 13px;">ISRC: ' + (data[i].spotify_isrc ?? '-') + '</span>' +
            '</td>';

            if (null !== data[i].apple_id) {
                let apple_is_deleted = data[i].apple_is_deleted === true ? '<span style="color: red;">[DELETED] </span>' : '';
                table += '<td>' +
                    '<a href="https://music.apple.com/us/album/' + data[i].apple_id + '" target="_blank">' + apple_is_deleted + data[i].apple_name + '</a>' +
                    '<br><span style="color: rgb(40, 40, 40); font-size: 13px;">' + data[i].apple_artists + '</span>' +
                    '<br><span style="color: rgb(80, 80, 80); font-size: 13px;">ISRC: ' + (data[i].apple_isrc ?? '-') + '</span>' +
                    '</td>';
            } else {
                table += '<td style="text-align: center"><span style="color: rgb(210, 210, 210)">—</span></td>';
            }

            let loaded = data[i].lo_track_id !== null ? '🟢' : '🟡';
            table += '<td style="text-align: center; padding: 0 8px 0 8px;">' + loaded + '</td>';

            //let status = data[i].is_approved === 1 ? '✅' : '❌';
            //table += '<td style="text-align: center; padding: 0 8px 0 8px;">' + status + '</td>';

            let tidal_is_deleted = data[i].tidal_is_deleted === true ? '<span style="color: red;">[DELETED] </span>' : '';
            table += '<td>' +
                '<a href="https://listen.tidal.com/track/' + data[i].tidal_id + '" target="_blank">' + tidal_is_deleted + data[i].tidal_name + '</a>' +
                '<br><span style="color: rgb(40, 40, 40); font-size: 13px;">' + data[i].tidal_artists + '</span>' +
                '<br><span style="color: rgb(80, 80, 80); font-size: 13px;">ISRC: ' + (data[i].tidal_isrc ?? '-') + '</span>' +
            '</td>';

            table += '</tr>';
        }
        table += '</table>';

        return table;
    }

    async generateTracksSpotify(elementId, albumId) {

        let url = '/v1/stats/tracks/not-found-spotify?';

        if (albumId) {
            url += 'albumId=' + albumId;
        }

        let data = await (await fetch(url)).json();
        document.getElementById(elementId).innerHTML = this.generateTableSpotify(data.data);
    }

    async generateTracksApple(elementId, albumId) {

        let url = '/v1/stats/tracks/not-found-apple?';

        if (albumId) {
            url += 'albumId=' + albumId;
        }

        let data = await (await fetch(url)).json();
        document.getElementById(elementId).innerHTML = this.generateTableApple(data.data);
    }

    async generateTracksTidal(elementId, albumId) {

        let url = '/v1/stats/tracks/not-found-tidal?';

        if (albumId) {
            url += 'albumId=' + albumId;
        }

        let data = await (await fetch(url)).json();
        document.getElementById(elementId).innerHTML = this.generateTableTidal(data.data);
    }

    generateTableSpotify(data) {

        let table = '<table class="table"><tr><th>#</th><th>Spotify</th></tr>';

        if (data.length === 0) {
            table += '<tr><td colspan="2" style="text-align: center">no data</td></tr>';
        }

        for (let i = 0; i < data.length; i++) {
            table += '<tr>';
            table += '<td style="text-align: center">' + (i + 1) + '</td>';
            table += '<td>' +
                '<a href="https://open.spotify.com/track/' + data[i].spotify_id + '" target="_blank">' + data[i].name + '</a>' +
                '<br><span style="color: rgb(40, 40, 40); font-size: 13px;">' + data[i].artists + '</span>' +
                '<br><span style="color: rgb(80, 80, 80); font-size: 13px;">ISRC: ' + (data[i].isrc ?? '-') + '</span>' +
            '</td>';
            table += '</tr>';
        }
        table += '</table>';
        return table;
    }

    generateTableApple(data) {
        let table = '<table class="table"><tr><th>#</th><th>Apple</th></tr>';

        if (data.length === 0) {
            table += '<tr><td colspan="2" style="text-align: center">no data</td></tr>';
        }

        for (let i = 0; i < data.length; i++) {
            table += '<tr>';
            table += '<td style="text-align: center">' + (i + 1) + '</td>';
            table += '<td>' +
                '<a href="https://music.apple.com/track/' + data[i].apple_id + '" target="_blank">' + data[i].name + '</a>' +
                '<br><span style="color: rgb(40, 40, 40); font-size: 13px;">' + data[i].artists + '</span>' +
                '<br><span style="color: rgb(80, 80, 80); font-size: 13px;">ISRC: ' + (data[i].isrc ?? '-') + '</span>' +
                '</td>';
            table += '</tr>';
        }
        table += '</table>';
        return table;
    }

    generateTableTidal(data) {
        let table = '<table class="table"><tr><th>#</th><th>Tidal</th></tr>';

        if (data.length === 0) {
            table += '<tr><td colspan="2" style="text-align: center">no data</td></tr>';
        }

        for (let i = 0; i < data.length; i++) {
            table += '<tr>';
            table += '<td style="text-align: center">' + (i + 1) + '</td>';
            table += '<td>' +
                '<a href="https://listen.tidal.com/track/' + data[i].tidal_id + '" target="_blank">' + data[i].name + '</a>' +
                '<br><span style="color: rgb(40, 40, 40); font-size: 13px;">' + data[i].artists + '</span>' +
                '<br><span style="color: rgb(80, 80, 80); font-size: 13px;">ISRC: ' + (data[i].isrc ?? '-') + '</span>' +
            '</td>';
            table += '</tr>';
        }
        table += '</table>';
        return table;
    }
}
