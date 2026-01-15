class Playlists {

    translates = [];

    async generate(elementId, params) {
        loader(1);
        let data = await this.getData(params);
        document.getElementById(elementId).innerHTML = this.generateTable(data.data.items);
        document.getElementById('total-count').innerHTML = 'Всего: ' + data.data.count;
        document.getElementById('data-order-id').setAttribute('data-order', (params.sort === '0' ? 'desc' : 'asc'));
        loader(0);

        generatePagination(data.data.count, 50, params.offset, 'redirectPlaylists');

        this.initTableSort();
    }

    async getData(params) {

        let url = '/v1/stats/playlists?' + new URLSearchParams(params).toString();

        return (await fetch(url)).json();
    }

    generateTable(data) {

        let table = '<table class="table">';

        table += '<tr>' +
            '<th data-order="asc" id="data-order-id" width="60px;">ID</th>' +
            '<th width="60px;">⚙️</th>' +
            '<th>Name</th>' +
            '<th>Translates</th>' +
            '<th>Founded tracks</th>' +
            '</tr>';

        if (data.length === 0) {
            table += '<tr><td colspan="7" style="text-align: center">no data</td></tr>';
        }

        for (let i = 0; i < data.length; i++) {

            let priority = ' ';
            if (data[i].priority === 1) {
                priority = ' <span style="font-size: 12px; color: darkgreen">(Высокий приоритет)</span> ';
            } else if (data[i].priority === -1) {
                priority = ' <span style="font-size: 12px; color: darkred">(Низкий приоритет)</span> ';
            }

            let refresh = '<span class="clicking" onclick="playlists.reset(' + data[i].id + ')">🔄</span>';

            let source = data[i].type === 1 ? 'Spotify' : 'Apple';
            let followed = data[i].is_followed ? 'Да' : '<span style="color: red">Нет</span>';

            let checked_at = '<span style="color: red">wait</span>';
            if (null !== data[i].checked_at) {
                checked_at = '<span style="font-style: italic; color: rgb(90, 90, 90)">' + timeConverter(data[i].checked_at) + '</span>';
            }

            table += '<tr>';
            table += '<td style="text-align: center">' + data[i].id + '</td>';

            let is_followed_int = data[i].is_followed ? 1 : 0;
            table += '<td style="text-align: center; font-size: 22px;">' +
                '<span class="clicking" onclick="playlists.editPlaylistModal(' + data[i].id + ', \'' +  escape(data[i].name.trim()) + '\', ' + is_followed_int + ')">⚙️</span>' +
                '</td>';

            table += '<td>' +
                '<a href="' + data[i].url + '" target="_blank">' + data[i].name.trim() + '</a>' + priority + refresh +
                '<div class="small">' +
                    'Источник: ' + source + '<br>' +
                    'Авто-обновление: ' + followed + '<br>' +
                    '<br>' +
                    'Checked: ' + checked_at +
                '</div>' +
            '</td>';

            table += '<td style="text-align: center;">' + data[i].count_translates + '</td>';
            table += '<td style="text-align: center;">' + data[i].count_tracks + ' / ' + data[i].total_tracks + '</td>';

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

                    redirectPlaylists();
                }
            });
        });
    }

    async reset(playlistId) {
        const options = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
        };

        let url = '/v1/playlists/' + playlistId + '/reset';

        fetch(url, options)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                return response.json();
            })
            .then(async _ => {
                alert('Отправлено на переобход');
                window.location.reload();
            })
            .catch(error => {
                console.error('There was a problem with your fetch operation:', error);
            });
    }

    addPlaylistModal() {
        let html = '';

        html += '' +
            '<table style="width: 98%">' +
            '<tr><td colspan="2"><br>Наименование:</td></tr>' +
            '<tr><td colspan="2"><input id="add-playlist-name" style="width: 100%" placeholder="Укажите наименование"></tr>' +
            '<tr><td colspan="2"><br>Ссылка на плейлист (Spotify/Apple):</td></tr>' +
            '<tr><td colspan="2"><input style="margin: 2px 0; width: 100%" id="add-playlist-url" placeholder="Укажите ссылку"></td></tr>' +
            '<tr><td colspan="2"><label><input type="checkbox" id="add-playlist-followed"> авто-обновляемый</label></td></tr>' +
            '<tr><td colspan="2"><br>User ID:</td></tr>' +
            '<tr><td colspan="2"><input type="number" id="add-playlist-user-id" placeholder="Укажите USER ID" style="width: 100%" value="1"></tr>' +
            '<tr><td colspan="2"><br>Сообщество:</td></tr>' +
            '<tr><td colspan="2"><input type="number" id="add-playlist-id" placeholder="Укажите UNION ID" style="width: 100%" onkeyup="playlists.checkUnion()" value="5"></tr>' +
            '<tr><td id="add-playlist-lo-avatar" style="width: 40px;"></td><td id="add-playlist-lo-name"></td></tr>' +
            '</table>' +
            '<div style="margin-top: 8px; text-align: center"><button id="add-playlist-btn" onclick="playlists.addPlaylist()">Добавить</button></div>';

        modalOpen('Новый плейлист', html, () => {playlists.checkUnion()});
    }

    async checkUnion() {
        document.getElementById('add-playlist-lo-avatar').innerHTML = '';
        document.getElementById('add-playlist-lo-name').innerHTML = '';
        document.getElementById('add-playlist-btn').setAttribute("disabled","disabled");

        let unionId = parseInt(document.getElementById('add-playlist-id').value);

        const options = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json'
            },
        };

        let url = 'https://api.lo.ink/v1/unions/' + unionId;

        let result = await fetch(url, options)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .catch(() => {
            });

        if (result !== undefined) {
            let photo = result.data.photo?.xs ?? result.data.photo?.xs.url;
            document.getElementById('add-playlist-lo-avatar').innerHTML = '<img style="width: 40px; height: 40px; border-radius: 50%" src="' + photo + '" alt="photo">';
            document.getElementById('add-playlist-lo-name').innerHTML = '<a href="https://lo.ink/c/' + result.data.id + '" target="_blank">' + result.data.name + '<br></a>';
            document.getElementById('add-playlist-btn').removeAttribute("disabled");
        }
    }

    async editPlaylistModal(playlistId, name, is_followed) {

        let url = '/v1/playlists/' + playlistId + '/translates';
        let result = await (await fetch(url)).json();

        let html = '';
        let checked = is_followed ? 'checked' : '';

        html += '' +
            '<div style="background-color: #5969fc24; margin: -8px -8px 8px -8px; padding: 8px;">' +
                '<table style="width: 98%">' +
                '<tr><td colspan="2"><input id="edit-playlist-name" value="' + unescape(name) + '" style="width: 100%" placeholder="Наименование"></tr>' +
                '<tr><td colspan="2"><label><input type="checkbox" ' + checked + ' id="edit-playlist-followed"> авто-обновляемый</label></td></tr>' +
                '</table>' +
                '<div style="margin-top: 8px; text-align: center"><button id="edit-playlist-btn" onclick="playlists.editPlaylist(' + playlistId + ')">Сохранить</button></div>' +
            '</div>';

        html += '<h3>Добавление перевода</h3>';

        let options = '<option value="ru">RU</option><option value="en">EN</option>';

        html += '' +
            '<form name="Translate">' +
                'Язык: <select style="width: 100%" name="lang">' + options + '</select><br><br>' +
                'Наименование: <input style="width: 100%" name="name" placeholder="Введите наименование"><br><br>' +
                'Описание: <textarea style="width: 100%" name="description" rows="4"></textarea><br><br>' +
                'Обложка: <input type="file" style="width: 100%" name="file">' +
                '<button type="submit" onclick="playlists.createTranslate(event, ' + playlistId + '); return false;">Отправить</button>' +
            '</form>';

        html += '<hr><h3>Переводы</h3>';

        for (let i = 0; i < result.data.items.length; i++) {
            let elem = result.data.items[i];

            html += '' +
                '<form name="Translate_' + elem['id'] + '">' +
                    '<div style="border: 1px solid #5969fc24; border-radius: 8px; padding: 8px; margin: 8px 0;">' +
                        '<table style="width: 100%;">' +
                            '<td>[' + elem['lang'] + '] ' + elem['name'] + '<br></td>' +
                            '<td><div style="float: right; cursor: pointer;" onclick="playlists.deleteTranslate(' + playlistId + ', ' + elem['id'] + ')">🗑️</div></td>' +
                        '</table>' +
                        '<table class="table-mini" style="width: 100%;" data-artist_id="' + playlistId + '">' +
                            '<tr>' +
                                '<td style="width: 60%"><label>Name</label><br><input name="name" id="translate-name-' + elem['id'] + '" style="width: 98%;" value="' + elem['name'] + '"></td>' +
                            '</tr>' +
                            '<tr>' +
                                '<td><label>Description</label><br><textarea rows="4" name="description" id="translate-description-' + elem['id'] + '" style="width: 98%;">' + elem['description'] + '</textarea></td>' +
                            '</tr>' +
                            '<tr>' +
                                '<td><input type="file" name="file" id="translate-file-' + elem['id'] + '"></td>' +
                            '</tr>' +
                            '<tr>' +
                                '<td><button type="submit" onclick="playlists.editTranslate(' + playlistId + ', ' + elem['id'] + ')">save</button></td>' +
                            '</tr>' +
                        '</table>' +
                    '</div>' +
                '</form>';
        }

        if (result.data.items.length === 0) {
            html += '<div style="text-align: center; padding: 20px 0;">No translates</div>';
        }

        modalOpen(name, html);
    }

    editPlaylist(playlistId) {
        let name = document.getElementById('edit-playlist-name').value;
        let isFollowed = document.getElementById('edit-playlist-followed').checked;

        let data = {
            name: name,
            isFollowed: isFollowed,
        };

        const options = {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        };

        let url = '/v1/playlists/' + playlistId;

        fetch(url, options)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                return response.json();
            })
            .then(async _ => {
                redirectPlaylists();
            })
            .catch(error => {
                console.error('There was a problem with your fetch operation:', error);
            });
    }

    addPlaylist() {
        let name = document.getElementById('add-playlist-name').value;
        let link = document.getElementById('add-playlist-url').value;
        let isFollowed = document.getElementById('add-playlist-followed').checked;
        let userId = document.getElementById('add-playlist-user-id').value;
        let unionId = document.getElementById('add-playlist-id').value;

        let data = {
            userId: userId,
            unionId: unionId,
            name: name,
            url: link,
            isFollowed: isFollowed,
            translates: this.translates
        };

        const options = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        };

        let url = '/v1/playlists';

        fetch(url, options)
            .then(async response => {
                if (!response.ok) {
                    let result = await response.text()
                    result = JSON.parse(result);

                    throw new Error(result.error.message);
                }
            })
            .then(async response => {
                redirectPlaylists();

                // this.translates = [];
                // modalClose();
            })
            .catch(error => {
                alert(error);
                console.error('There was a problem with your fetch operation:', error);
            });
    }

    createTranslate(event, playlistId) {
        event.preventDefault();

        const form = document.querySelector('form[name="Translate"]');
        const formData = new FormData(form);

        const options = {
            method: 'POST',
            body: formData
        };

        let url = '/v1/playlists/' + playlistId + '/translates';

        fetch(url, options)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(async _ => {
                await this.reopenPlaylist(playlistId);
            })
            .catch(error => {
                console.error('There was a problem with your fetch operation:', error);
            });
    }

    editTranslate(playlistId, translateId) {
        event.preventDefault();

        const form = document.querySelector('form[name="Translate_' + translateId + '"]');
        const formData = new FormData(form);

        const options = {
            method: 'POST',
            body: formData
        };

        let url = '/v1/playlists/' + playlistId + '/translates/' + translateId;

        fetch(url, options)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(async _ => {
                await this.reopenPlaylist(playlistId);
            })
            .catch(error => {
                console.error('There was a problem with your fetch operation:', error);
            });
    }

    deleteTranslate(playlistId, translateId) {
        const options = {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
        };

        let url = '/v1/playlists/' + playlistId + '/translates/' + translateId;

        fetch(url, options)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(async _ => {
                await this.reopenPlaylist(playlistId);
            })
            .catch(error => {
                console.error('There was a problem with your fetch operation:', error);
            });
    }

    async reopenPlaylist(playlistId) {
        let name = document.getElementById('edit-playlist-name').value;
        let isFollowed = document.getElementById('edit-playlist-followed').checked;

        await this.editPlaylistModal(playlistId, escape(name), isFollowed);
    }
}
