class Artists {

    async generate(elementId, params) {
        loader(1);
        let data = await this.getData(params);
        document.getElementById(elementId).innerHTML = this.generateTable(data.data.items);
        document.getElementById('total-count').innerHTML = 'Всего: ' + data.data.count;
        document.getElementById('data-order-id').setAttribute('data-order', (params.sort === '0' ? 'desc' : 'asc'));
        loader(0);

        generatePagination(data.data.count, 50, params.offset, 'redirect');

        this.initTableSort();
    }

    async getData(params) {

        let url = '/v1/stats/artists?' + new URLSearchParams(params).toString();

        return (await fetch(url)).json();
    }

    generateTable(data) {

        let table = '<table class="table">';

        table += '<tr>' +
            '<th data-order="asc" id="data-order-id">ID</th>' +
            '<th>⚙️</th>' +
            '<th>🔍</th>' +
            '<th>Artist</th>' +
            '<th>🔴 Not mapped</th>' +
            '<th>⚪️ Conflict</th>' +
            '<th>🟡 Wait</th>' +
            '<th>🟢 Loaded</th>' +
            '</tr>';

        if (data.length === 0) {
            table += '<tr><td colspan="7" style="text-align: center">no data</td></tr>';
        }

        for (let i = 0; i < data.length; i++) {
            table += '<tr>';
            table += '<td style="text-align: center">' + data[i].id + '</td>';

            let name_esc = escape((data[i].name ?? '').trim());
            let lo_name_esc = escape((data[i].lo_name ?? '').trim());
            let lo_description_esc = escape((data[i].lo_description ?? '').trim());

            table += '<td style="text-align: center; font-size: 22px;">' +
                '<span class="clicking" onclick="artists.getSocials(' + data[i].id + ', \'' + name_esc  + '\', \'' + lo_name_esc  + '\', \'' +  lo_description_esc + '\', ' +  data[i].lo_category_id + ')">⚙️</span>' +
            '</td>';

            table += '<td style="text-align: center">' +
                '<span class="clicking" onClick="artists.searchByName(' + data[i].id + ', \'' + name_esc  + '\', \'' + lo_name_esc  + '\', \'' +  lo_description_esc + '\', ' +  data[i].lo_category_id + ')">🔍</span>' +
            '</td>';

            let spotify_checked_at = data[i].spotify_checked_at !== null && data[i].spotify_checked_at > 0 ? '<span style="font-style: italic; color: rgb(90, 90, 90)">' + timeConverter(data[i].spotify_checked_at) + '</span>' : '<span style="color: red">wait</span>';

            let tidal_checked_at = '<span style="color: red">wait</span>';
            if (data[i].tidal_checked_at < data[i].spotify_checked_at && null !== data[i].tidal_checked_at) {
                tidal_checked_at = '<span style="font-style: italic; color: orangered">' + timeConverter(data[i].tidal_checked_at) + '</span>';
            } else if (data[i].tidal_checked_at >= data[i].spotify_checked_at && null !== data[i].tidal_checked_at) {
                tidal_checked_at = '<span style="font-style: italic; color: rgb(90, 90, 90)">' + timeConverter(data[i].tidal_checked_at) + '</span>';
            }

            let apple_checked_at = '<span style="color: red">wait</span>';
            if (data[i].apple_checked_at < data[i].tidal_checked_at && null !== data[i].apple_checked_at) {
                apple_checked_at = '<span style="font-style: italic; color: orangered">' + timeConverter(data[i].apple_checked_at) + '</span>';
            } else if (data[i].apple_checked_at >= data[i].tidal_checked_at && null !== data[i].apple_checked_at) {
                apple_checked_at = '<span style="font-style: italic; color: rgb(90, 90, 90)">' + timeConverter(data[i].apple_checked_at) + '</span>';
            }

            let merged_at = '<span style="color: red">wait</span>';
            if ((data[i].merged_at < data[i].apple_checked_at || null === data[i].apple_checked_at) && null !== data[i].merged_at) {
                merged_at = '<span style="font-style: italic; color: orangered;">' + timeConverter(data[i].merged_at) + '</span>';
            } else if (data[i].merged_at >= data[i].apple_checked_at && null !== data[i].merged_at) {
                merged_at = '<span style="font-style: italic; color: rgb(90, 90, 90)">' + timeConverter(data[i].merged_at) + '</span>';
            }

            let checked_at = '<span style="color: red">wait</span>';
            if (null !== data[i].checked_at) {
                checked_at = '<span style="font-style: italic; color: rgb(90, 90, 90)">' + timeConverter(data[i].checked_at) + '</span>';
            }

            let priority = '';
            if (data[i].priority === 1) {
                priority = ' <span style="font-size: 12px; color: darkgreen">(Высокий приоритет)</span> ';
            } else if (data[i].priority === -1) {
                priority = ' <span style="font-size: 12px; color: darkred">(Низкий приоритет)</span> ';
            }

            let refresh = '<span class="clicking" onclick="artists.reset(' + data[i].id + ')">🔄</span>';

            table += '<td>' +
                '<a href="https://lo.ink/c/' + data[i].union_id + '" target="_blank">' + data[i].name + '</a> ' + priority + refresh +
                '<div class="small">' +
                    'Spotify: ' + data[i].spotify_count_socials + ' socials, ' + data[i].spotify_count_albums + ' albums, ' + spotify_checked_at + ' <br>' +
                    'Tidal: ' + data[i].tidal_count_socials + ' socials, ' + data[i].tidal_count_albums + ' albums, ' + tidal_checked_at + ' <br>' +
                    'Apple: ' + data[i].apple_count_socials + ' socials, ' + data[i].apple_count_albums + ' albums, ' + apple_checked_at + '  <br>' +
                    '<br>' +
                    'Mapped: ' + merged_at + ', Checked: ' + checked_at +
                '</div>' +
            '</td>';

            table += '<td style="text-align: center">' +
                '<a href="/table/albums-not-found?artistId=' + data[i].id + '">' + data[i].spotify_free + '</a>' +
                ' / ' +
                '<a href="/table/albums-not-found?artistId=' + data[i].id + '">' + data[i].tidal_free + '</a>' +
                ' / ' +
                '<a href="/table/albums-not-found?artistId=' + data[i].id + '">' + data[i].apple_free + '</a>' +
            '</td>';

            let conflicts = data[i].count_approved - data[i].count_approved_with_tracks + data[i].count_conflicts;

            let count_tracks_conflicts = '<span style="color: rgb(210, 210, 210)">—</span>';
            if (conflicts !== 0) {
                count_tracks_conflicts = '<a href="/table/albums?artistId=' + data[i].id + '&status=2">' + (conflicts) + '</a>';
            }

            table += '<td style="text-align: center">' +
                count_tracks_conflicts +
            '</td>';

            table += '<td style="text-align: center">' +
                '<a href="/table/albums?artistId=' + data[i].id + '&status=1">' + data[i].count_approved + '</a>' +
                '</td>';

            if (
                data[i].spotify_free === 0 &&
                conflicts === 0 &&
                data[i].count_loaded > 0 &&
                data[i].count_loaded === data[i].count_approved
            ) {
                table += '<td style="text-align: center">' +
                    '✅' +
                '</td>';
            } else {
                table += '<td style="text-align: center">' +
                    '<a href="/table/albums?artistId=' + data[i].id + '&status=1">' + data[i].count_loaded + '</a>' +
                '</td>';
            }

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

                    redirect();
                }
            });
        });
    }

    async reset(artistId) {
        const options = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
        };

        let url = '/v1/artists/' + artistId + '/reset';

        fetch(url, options)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                return response.json();
            })
            .then(async _ => {
                alert('Отправлено на полный переобход');
                window.location.reload();
            })
            .catch(error => {
                console.error('There was a problem with your fetch operation:', error);
            });
    }

    searchByName(artistId, name, loName, loDescription, loCategoryId) {
        artists.getSocials(artistId, name, loName, loDescription, loCategoryId).then(r => {});
        window.open('https://www.google.ru/search?ie=UTF-8&q=' + unescape(name), '_blank');
        window.open('https://yandex.ru/search/?text=' + unescape(name), '_blank');
    }

    addArtistModal(name, isNew, url) {

        name = name !== undefined ? unescape(name) : '';
        isNew = isNew !== undefined ? isNew : false;
        url = url !== undefined ? unescape(url) : '';

        if (name !== '' && url !== '') {
            window.open(url, '_blank');
            window.open('https://open.spotify.com/search/' + name + '/artists', '_blank');
            window.open('https://listen.tidal.com/search/artists?q=' + name, '_blank');
        }

        let html = '';

        html += '' +
            '<table style="width: 98%">' +
                '<tr><td colspan="2"><br>Наименование:</td></tr>' +
                '<tr><td colspan="2"><input id="add-union-name" value="' + name + '" style="width: 100%" placeholder="Укажите наименование"></tr>' +
                '<tr><td colspan="2"><br>Сообщество: <div style="float: right"><label><input type="checkbox" id="add-union-new" onchange="artists.changeUnion()"> создать новое</label></div> </td></tr>' +
                '<tr><td colspan="2"><input type="number" id="add-union-id" placeholder="Укажите UNION ID" style="width: 100%" onkeyup="artists.checkUnion()"></tr>' +
                '<tr><td id="add-union-lo-avatar" style="width: 40px;"></td><td id="add-union-lo-name"></td></tr>' +
                '<tr><td id="add-union-textarea" style="display: none;" colspan="2">' +
                    '<br>Название сообщества:' +
                    '<input id="add-union-community-name" value="' + name + '" style="width: 100%" placeholder="Укажите название"><br><br>' +
                    '<textarea id="add-union-description" style="width: 100%" rows="6" placeholder="Описание сообщества"></textarea>' +
                '</td></tr>' +
                '<tr><td id="add-union-category" style="display: none;" colspan="2">' +
                    '<select id="add-union-categoryId" style="width: 100%">' +
                        '<option value="-1">Не выбрано</option>' +
                        '<option value="111">Музыкант</option>' +
                        '<option value="109">Музыкальная группа</option>' +
                        '<option value="108">Исполнитель</option>' +
                        '<option value="110">DJ</option>' +
                        '<option value="112">Лейбл</option>' +
                        '<option value="113">Радио</option>' +
                    '</select>' +
                '</td></tr>' +
                '<tr><td colspan="2"><br>Соц. сети:</td></tr>' +
                '<tr><td colspan="2"><input style="margin: 2px 0; width: 100%" id="add-social-0" placeholder="Ссылка на Spotify/Tidal/Apple" value="' + url + '"></td></tr>' +
                '<tr><td colspan="2"><input style="margin: 2px 0; width: 100%" id="add-social-1" placeholder="Ссылка на Spotify/Tidal/Apple"></td></tr>' +
                '<tr><td colspan="2"><input style="margin: 2px 0; width: 100%" id="add-social-2" placeholder="Ссылка на Spotify/Tidal/Apple"></td></tr>' +
                '<tr><td colspan="2"><input style="margin: 2px 0; width: 100%" id="add-social-3" placeholder="Ссылка на Spotify/Tidal/Apple"></td></tr>' +
                '<tr><td colspan="2"><input style="margin: 2px 0; width: 100%" id="add-social-4" placeholder="Ссылка на Spotify/Tidal/Apple"></td></tr>' +
                '<tr><td colspan="2"><input style="margin: 2px 0; width: 100%" id="add-social-5" placeholder="Ссылка на Spotify/Tidal/Apple"></td></tr>' +
                '<tr><td colspan="2"><input style="margin: 2px 0; width: 100%" id="add-social-6" placeholder="Ссылка на Spotify/Tidal/Apple"></td></tr>' +
                '<tr><td colspan="2"><input style="margin: 2px 0; width: 100%" id="add-social-7" placeholder="Ссылка на Spotify/Tidal/Apple"></td></tr>' +
            '</table>' +
            '<div style="margin-top: 8px; text-align: center"><button id="add-social-btn" onclick="artists.addArtist()" disabled>Добавить</button></div>';

        modalOpen('Новый артист', html, function () {
            if (isNew) {
                document.getElementById('add-union-new').checked = true;
                artists.changeUnion();
            }
        });
    }

    async changeUnion() {
        let needCreate = document.getElementById('add-union-new').checked;

        document.getElementById('add-union-lo-avatar').innerHTML = '';
        document.getElementById('add-union-lo-name').innerHTML = '';

        if (needCreate) {
            document.getElementById('add-union-id').disabled = true;
            document.getElementById('add-union-textarea').style.display = "";
            document.getElementById('add-union-category').style.display = "";
            document.getElementById('add-social-btn').disabled = false;
        } else {
            document.getElementById('add-union-id').disabled = false;
            document.getElementById('add-union-textarea').style.display = "none";
            document.getElementById('add-union-category').style.display = "none";
            document.getElementById('add-social-btn').disabled = true;
        }
    }

    async checkUnion() {
        document.getElementById('add-union-lo-avatar').innerHTML = '';
        document.getElementById('add-union-lo-name').innerHTML = '';
        document.getElementById('add-union-name').value = '';
        document.getElementById('add-social-btn').setAttribute("disabled","disabled");

        let unionId = parseInt(document.getElementById('add-union-id').value);

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
            document.getElementById('add-union-lo-avatar').innerHTML = '<img style="width: 40px; height: 40px; border-radius: 50%" src="' + photo + '" alt="photo">';
            document.getElementById('add-union-lo-name').innerHTML = '<a href="https://lo.ink/c/' + result.data.id + '" target="_blank">' + result.data.name + '<br></a>';
            document.getElementById('add-union-name').value = result.data.name;
            document.getElementById('add-social-btn').removeAttribute("disabled");
        }
    }

    addArtist() {
        let unionId = document.getElementById('add-union-id').value;
        unionId = unionId === "" ? null : unionId;

        let name = document.getElementById('add-union-name').value;
        let communityName = document.getElementById('add-union-community-name').value;
        let description = document.getElementById('add-union-description').value;
        let categoryId = parseInt(document.getElementById('add-union-categoryId').value);

        if (unionId === null && categoryId === -1) {
            alert('Выберите категорию!');
            return;
        }

        let links = [];

        for (let i = 0; i < 8; i++) {
            let val = document.getElementById('add-social-' + i).value.trim();

            if (val !== '') {
                links.push(val);
            }
        }

        let data = {
            unionId: unionId,
            name: name,
            communityName: communityName,
            description: description,
            categoryId: categoryId,
            links: links
        };

        const options = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        };

        let url = '/v1/artists';

        fetch(url, options)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(async _ => {
                //redirect();
                window.location.reload();
            })
            .catch(error => {
                console.error('There was a problem with your fetch operation:', error);
            });
    }

    selectedOption(categoryId, value) {
        return categoryId === value ? 'selected' : '';
    }

    async getSocials(artistId, name, loName, loDescription, loCategoryId) {

        let types = this.socialList();

        let url = '/v1/artists/' + artistId + '/socials';
        let result = await (await fetch(url)).json();

        let html = '';

        let select = '' +
            '<select id="edit-artist-categoryId" style="width: 100%">' +
                '<option value="-1">Не выбрано</option>' +
                '<option value="111" ' + artists.selectedOption(111, loCategoryId) + '>Музыкант</option>' +
                '<option value="109" ' + artists.selectedOption(109, loCategoryId) + '>Музыкальная группа</option>' +
                '<option value="108" ' + artists.selectedOption(108, loCategoryId) + '>Исполнитель</option>' +
                '<option value="110" ' + artists.selectedOption(110, loCategoryId) + '>DJ</option>' +
                '<option value="112" ' + artists.selectedOption(112, loCategoryId) + '>Лейбл</option>' +
                '<option value="113" ' + artists.selectedOption(113, loCategoryId) + '>Радио</option>' +
            '</select>';

        html += '' +
            '<div style="background-color: #5969fc24; margin: -8px -8px 12px -8px; padding: 8px;">' +
                '<table style="width: 98%">' +
                '<tr>' +
                    '<td colspan="1">' +
                        '<label>Название: <input id="edit-artist-description" value="' + unescape(name) + '" style="width: 90%" placeholder="Название"></label><br><br>' +
                        '<label>Название в LO: <input id="edit-artist-lo-name" value="' + unescape(loName) + '" style="width: 90%" placeholder="Название в LO"></label><br><br>' +
                        '<label>Описание в LO: <textarea id="edit-artist-lo-description" rows="6" style="width: 90%" placeholder="Описание в LO">' + unescape(loDescription) + '</textarea></label><br><br>' +
                        '<label>Категория: ' + select + '</label>' +
                    '</td>' +
                '</tr>' +
                '</table>' +
                '<div style="margin-top: 8px; text-align: center"><button id="edit-playlist-btn" onclick="artists.editArtist(' + artistId + ')">Сохранить</button></div>' +
            '</div>';

        html += '<div style="background-color: #5969fc24; margin: -8px -8px 8px -8px; padding: 8px;">' +
            '<table class="table-mini">' +
                '<td><label>Url</label><br><input value="" id="social-url"></td>' +
                '<td><label>Name</label><br><input value="" id="social-description"></td>' +
                '<td><label>&nbsp;</label><br><button onclick="artists.addSocial(' + artistId + ')">add</button></td>' +
            '</table>' +
        '</div>';

        for (let i = 0; i < result.data.items.length; i++) {
            let elem = result.data.items[i];

            html += '<div style="border: 1px solid #5969fc24; border-radius: 8px; padding: 8px; margin: 8px 0;">' +
                '<table style="width: 100%;">' +
                    '<td><a href="' + elem['url'] + '" target="_blank">' + types[elem['type']] + '</a><br></td>' +
                    '<td><div style="float: right; cursor: pointer;" onclick="artists.deleteSocial(' + artistId + ', ' + elem['id'] + ')">🗑️</div></td>' +
                '</table>' +
                '<table class="table-mini" style="width: 100%;" data-artist_id="' + artistId + '">' +
                    '<td style="width: 60%"><label>Url</label><br><input id="social-url-' + elem['id'] + '" style="width: 95%;" value="' + elem['url'] + '"></td>' +
                    '<td><label>Name</label><br><input id="social-description-' + elem['id'] + '" style="width: 90%;" value="' + elem['description'] + '"></td>' +
                    '<td><label>&nbsp;</label><br><button onclick="artists.editSocial(' + artistId + ', ' + elem['id'] + ')">save</button></td>' +
                '</table>' +
            '</div>';
        }

        if (result.data.items.length === 0) {
            html += '<div style="text-align: center; padding: 20px 0;">No socials</div>';
        }

        modalOpen(name, html);
    }

    editArtist(artistId) {
        let description = document.getElementById('edit-artist-description').value;
        let loName = document.getElementById('edit-artist-lo-name').value;
        let loDescription = document.getElementById('edit-artist-lo-description').value;
        let loCategoryId = document.getElementById('edit-artist-categoryId').value;

        let data = {
            description: description,
            loName: loName,
            loDescription: loDescription,
            loCategoryId: loCategoryId,
        };

        const options = {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        };

        let url = '/v1/artists/' + artistId;

        fetch(url, options)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                return response.json();
            })
            .then(async _ => {
                alert('Сохранено!');
            })
            .catch(error => {
                console.error('There was a problem with your fetch operation:', error);
            });
    }

    addSocial(artistId) {
        let url = document.getElementById('social-url').value;
        let description = document.getElementById('social-description').value;

        let data = {
            url: url,
            description: description
        };

        const options = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        };

        url = '/v1/artists/' + artistId + '/socials';

        fetch(url, options)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(async _ => {
                await this.getSocials(artistId, null);
            })
            .catch(error => {
                console.error('There was a problem with your fetch operation:', error);
            });
    }

    editSocial(artistId, socialId) {
        let url = document.getElementById('social-url-' + socialId).value;
        let description = document.getElementById('social-description-' + socialId).value;

        let data = {
            url: url,
            description: description
        };

        const options = {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        };

        url = '/v1/artists/' + artistId + '/socials/' + socialId;

        fetch(url, options)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(async _ => {
                await this.getSocials(artistId, null);
            })
            .catch(error => {
                console.error('There was a problem with your fetch operation:', error);
            });
    }

    deleteSocial(artistId, socialId) {
        const options = {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json'
            },
        };

        let url = '/v1/artists/' + artistId + '/socials/' + socialId;

        fetch(url, options)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(async _ => {
                await this.getSocials(artistId, null);
            })
            .catch(error => {
                console.error('There was a problem with your fetch operation:', error);
            });
    }

    socialList() {
        return ['Tidal', 'Spotify', 'Apple'];
    }
}
