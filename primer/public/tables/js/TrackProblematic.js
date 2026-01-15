class TrackProblematic {
    async generate(elementId, params) {
        try {
            loader(1);
            const response = await this.getData(params);

            this.validateResponse(response);

            const { items, count } = this.extractData(response);

            document.getElementById(elementId).innerHTML = this.generateTable(items);
            document.getElementById('total-count').innerHTML = `Всего: ${count}`;

            if (count > 0) {
                const offset = parseInt(params.offset) || 0;
                generatePagination(count, 50, offset, 'redirect');
            }
        } catch (error) {
            this.handleError(error, elementId);
        } finally {
            loader(0);
        }
    }

    async getData(params) {
        const url = '/v1/stats/track-problematic?' + new URLSearchParams(params).toString();
        const response = await fetch(url);
        const responseText = await response.text();

        try {
            return JSON.parse(responseText);
        } catch (parseError) {
            throw new Error('Ошибка при разборе ответа от сервера');
        }
    }

    validateResponse(response) {
        if (!response || typeof response !== 'object') {
            throw new Error('Некорректный формат ответа от сервера');
        }

        if (response.success === false) {
            throw new Error(response.error?.message || 'Ошибка при загрузке данных');
        }
    }

    extractData(response) {
        const responseData = response.data || response;
        let items = [];
        let count = 0;

        if (responseData?.data?.items && Array.isArray(responseData.data.items)) {
            items = responseData.data.items;
            count = responseData.data.count || items.length;
        } else if (Array.isArray(responseData.items)) {
            items = responseData.items;
            count = responseData.count || items.length;
        } else if (Array.isArray(responseData)) {
            items = responseData;
            count = items.length;
        } else if (responseData && typeof responseData === 'object') {
            items = responseData.data || [];
            count = responseData.count || items.length;
        }

        return { items, count };
    }

    generateTable(data) {
        if (!data || data.length === 0) {
            return '<div class="no-data">Нет данных для отображения</div>';
        }

        const getStatusText = (status) => {
            switch(status) {
                case 5: return 'В работе';
                case 4: return 'Нет ссылок';
                case 3: return 'Отсутствует Tidal';
                case 2: return 'С isrc';
                case 1: return 'Без isrc';
                case 0: return 'На переобходе';
                default: return 'Неизвестно';
            }
        };

        const rows = data
            .filter(item => item && typeof item === 'object')
            .map(item => {
                const searchQuery = encodeURIComponent(`${item.artist_name || ''} ${item.name || ''}`);
                const tidalSearchUrl = `https://tidal.com/search?q=${searchQuery}`;
                const spotifySearchUrl = `https://open.spotify.com/search/${searchQuery}`;
                const artistSearchQuery = (item.artist_name || '').replace(/\s+/g, '+');
                const artistSearchUrl = `https://music.lo.media/table/artists?search=${artistSearchQuery}&sort=1&field=id&offset=0`;

                const searchLinks = `
                <a href="${tidalSearchUrl}" target="_blank">🎵Tidal</a></br>
                <a href="${spotifySearchUrl}" target="_blank">🎵Spotify</a></br>
                <a href="${artistSearchUrl}" target="_blank">👤LO</a>
            `;

                const tidalCell = item.tidal_url ?
                    `<a href="${item.tidal_url}" target="_blank" style="font-size: 10px;">${item.tidal_url}</a> 
                        <span onclick="pageController.trackProblematic.clearUrl(${item.id}, 'tidal')" 
                                style="color: red; cursor: pointer; font-size: 12px; margin-left: 5px;" 
                                title="Очистить URL">&times;</span>` :
                    `<input type="text" placeholder="Tidal URL" style="width:100px;font-size:10px;" onchange="pageController.trackProblematic.updateUrl(${item.id}, 'tidal', this.value)">`;

                const spotifyCell = item.spotify_url ?
                    `<a href="${item.spotify_url}" target="_blank" style="font-size: 10px;">${item.spotify_url}</a> 
                        <span onclick="pageController.trackProblematic.clearUrl(${item.id}, 'spotify')" 
                            style="color: red; cursor: pointer; font-size: 12px; margin-left: 5px;" 
                            title="Очистить URL">&times;</span>` :
                    `<input type="text" placeholder="Spotify URL" style="width:100px;font-size:10px;" onchange="pageController.trackProblematic.updateUrl(${item.id}, 'spotify', this.value)">`;

                const statusButtons = `
                <div style="display: flex; flex-direction: column; gap: 2px; font-size: 10px;">
                    <button onclick="pageController.trackProblematic.updateStatus(${item.lo_track_id}, 0)" style="padding: 1px 3px; font-size: 9px;" ${item.status == '0' ? 'disabled' : ''}>На переобход</button>
                    <button onclick="pageController.trackProblematic.updateStatus(${item.lo_track_id}, 1)" style="padding: 1px 3px; font-size: 9px;" ${item.status == '1' ? 'disabled' : ''}>Без isrc</button>
                    <button onclick="pageController.trackProblematic.updateStatus(${item.lo_track_id}, 2)" style="padding: 1px 3px; font-size: 9px;" ${item.status == '2' ? 'disabled' : ''}>С isrc</button>
                    <button onclick="pageController.trackProblematic.updateStatus(${item.lo_track_id}, 3)" style="padding: 1px 3px; font-size: 9px;" ${item.status == '3' ? 'disabled' : ''}>Отсутсвует Tidal</button>
                    <button onclick="pageController.trackProblematic.updateStatus(${item.lo_track_id}, 4)" style="padding: 1px 3px; font-size: 9px;" ${item.status == '4' ? 'disabled' : ''}>Нет ссылок</button>
                    <button onclick="pageController.trackProblematic.updateStatus(${item.lo_track_id}, 5)" style="padding: 1px 3px; font-size: 9px;" ${item.status == '5' ? 'disabled' : ''}>В работе</button>
                </div>
            `;

                return `
                <tr data-track-id="${item.id}">
                    <td><input type="checkbox" class="track-checkbox" value="${item.lo_track_id}"></td>
                    <td>${item.id || ''}</td>
                    <td>${item.lo_track_id || ''}</td>
                    <td>${item.artist_id || ''}</td>
                    <td>${item.artist_name || ''}</td>
                    <td>${item.name || ''}</td>
                    <td>${searchLinks}</td>
                    <td>${tidalCell}</td>
                    <td>${spotifyCell}</td>
                    <td>${getStatusText(item.status)}</td>
                    <td style="text-align: center;">${statusButtons}</td>
                    <td style="text-align: center; font-size: 22px;">
                        <span class="clicking" onclick="pageController.trackProblematic.getSocials(${item.artist_id || 0}, decodeURIComponent('${encodeURIComponent(item.artist_name || '')}'), decodeURIComponent('${encodeURIComponent(item.tidal_url || '')}'), decodeURIComponent('${encodeURIComponent('problematic' || '')}'))">⚙️</span>
                    </td>
                </tr>
                `;
            }).join('');

        return `
        <table class="table">
            <thead>
                <tr>
                    <th><input type="checkbox" disabled></th>
                    <th>ID</th>
                    <th>LO_ID</th>
                    <th>ID Артиста</th>
                    <th>Исполнитель</th>
                    <th>Название трека</th>
                    <th>Поиск</th>
                    <th>Tidal</th>
                    <th>Spotify</th>
                    <th>Статус</th>
                    <th>Обновить статус</th>
                    <th>⚙️</th>
                </tr>
            </thead>
            <tbody>${rows}</tbody>
        </table>
    `;
    }

    async getSocials(artistId, artistName, tidalUrl = '', trackName = 'TrackProblematic') {
        if (!artistId || artistId === 0) {
            alert('ID артиста не найден');
            return;
        }

        try {
            const url = `/v1/artists/${artistId}/socials`;
            const result = await fetch(url);
            const data = await result.json();

            this.showSocialsModal(artistId, artistName, data.data.items, tidalUrl, trackName);
        } catch (error) {
            console.error('Ошибка загрузки социальных сетей:', error);
            alert('Ошибка загрузки данных');
        }
    }

    showSocialsModal(artistId, artistName, socials, tidalUrl = '', trackName = 'TrackProblematic') {
        const types = ['Tidal', 'Spotify', 'Apple'];

        let processedTidalUrl = tidalUrl;
        if (tidalUrl && !tidalUrl.includes('listen.tidal.com') && tidalUrl.includes('tidal.com')) {
            processedTidalUrl = tidalUrl.replace('tidal.com', 'listen.tidal.com');
        }

        let html = `
        <div style="background-color: #5969fc24; margin: -8px -8px 8px -8px; padding: 8px;">
            <table class="table-mini">
                <td><label>Url</label><br><input value="${decodeURIComponent(processedTidalUrl)}" id="social-url"></td>
                <td><label>Name</label><br><input value="${trackName}" id="social-description"></td>
                <td><label>&nbsp;</label><br><button onclick="pageController.trackProblematic.addSocial(${artistId})">add</button></td>
            </table>
        </div>
    `;

        socials.forEach(social => {
            html += `
            <div style="border: 1px solid #5969fc24; border-radius: 8px; padding: 8px; margin: 8px 0;">
                <table style="width: 100%;">
                    <td><a href="${social.url}" target="_blank">${types[social.type] || 'Unknown'}</a></td>
                    <td><div style="float: right; cursor: pointer;" onclick="pageController.trackProblematic.deleteSocial(${artistId}, ${social.id})">🗑️</div></td>
                </table>
                <table class="table-mini" style="width: 100%;">
                    <td style="width: 60%"><label>Url</label><br><input id="social-url-${social.id}" style="width: 95%;" value="${social.url}"></td>
                    <td><label>Name</label><br><input id="social-description-${social.id}" style="width: 90%;" value="${social.description}"></td>
                    <td><label>&nbsp;</label><br><button onclick="pageController.trackProblematic.editSocial(${artistId}, ${social.id})">save</button></td>
                </table>
            </div>
        `;
        });

        if (socials.length === 0) {
            html += '<div style="text-align: center; padding: 20px 0;">No socials</div>';
        }

        modalOpen(`Социальные сети: ${artistName}`, html);
    }

    async addSocial(artistId) {
        const url = document.getElementById('social-url').value;
        const description = document.getElementById('social-description').value;

        if (!url.trim()) {
            alert('Введите URL');
            return;
        }

        try {
            const response = await fetch(`/v1/artists/${artistId}/socials`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ url, description })
            });

            if (response.ok) {
                const modalHeaderElement = document.getElementById('modal-header');
                const artistName = modalHeaderElement ? modalHeaderElement.textContent.replace('Социальные сети: ', '') : '';
                this.getSocials(artistId, artistName);
            }
        } catch (error) {
            console.error('Ошибка добавления:', error);
            alert('Ошибка добавления');
        }
    }

    async editSocial(artistId, socialId) {
        const url = document.getElementById(`social-url-${socialId}`).value;
        const description = document.getElementById(`social-description-${socialId}`).value;

        try {
            const response = await fetch(`/v1/artists/${artistId}/socials/${socialId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ url, description })
            });

            if (response.ok) {
                alert('Сохранено!');
            }
        } catch (error) {
            console.error('Ошибка сохранения:', error);
            alert('Ошибка сохранения');
        }
    }

    async deleteSocial(artistId, socialId) {
        if (!confirm('Удалить социальную сеть?')) return;

        try {
            const response = await fetch(`/v1/artists/${artistId}/socials/${socialId}`, {
                method: 'DELETE'
            });

            if (response.ok) {
                const modalHeaderElement = document.getElementById('modal-header');
                const artistName = modalHeaderElement ? modalHeaderElement.textContent.replace('Социальные сети: ', '') : '';
                this.getSocials(artistId, artistName);
            }
        } catch (error) {
            console.error('Ошибка удаления:', error);
            alert('Ошибка удаления');
        }
    }


    handleError(error, elementId) {
        const errorElement = document.getElementById(elementId);
        if (errorElement) {
            errorElement.innerHTML = `
                <div class="error">
                    Ошибка при загрузке данных: ${error.message || 'Неизвестная ошибка'}
                </div>
            `;
        }
    }

    async updateStatus(trackId, newStatus) {
        try {
            loader(1);

            const response = await fetch(`/v1/stats/track-problematic/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ loTrackId: trackId, status: newStatus })
            });

            if (!response.ok) {
                const errText = await response.text();
                throw new Error(`Ошибка обновления статуса: ${response.status} ${response.statusText} ${errText}`);
            }

            location.reload();
        } catch (error) {
            alert('Не удалось обновить статус трека');
        } finally {
            loader(0);
        }
    }

    async updateUrl(trackId, platform, url) {
        if (!url.trim()) return;

        const body = {};
        if (platform === 'tidal') {
            body.tidal_url = url;
        } else if (platform === 'spotify') {
            body.spotify_url = url;
        }

        const response = await fetch(`/v1/stats/track-problematic/${trackId}/url`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(body)
        });

        if (response.ok) {
            const tr = document.querySelector(`tr[data-track-id="${trackId}"]`);
            if (tr) {
                const tds = tr.querySelectorAll('td');
                const platformIndex = platform === 'tidal' ? 7 : 8;
                const td = tds[platformIndex];
                if (td) {
                    const linkHtml = `<a href="${url}" target="_blank" style="font-size: 10px;">${url}</a> 
                                  <span onclick="pageController.trackProblematic.clearUrl(${trackId}, '${platform}')" 
                                        style="color: red; cursor: pointer; font-size: 12px; margin-left: 5px;" 
                                        title="Очистить URL">&times;</span>`;
                    td.innerHTML = linkHtml;
                }
            }
        }
    }

    async clearUrl(trackId, platform) {
        const body = {};
        if (platform === 'tidal') {
            body.tidal_url = '';
        } else if (platform === 'spotify') {
            body.spotify_url = '';
        }

        const response = await fetch(`/v1/stats/track-problematic/${trackId}/url`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(body)
        });

        if (response.ok) {
            const tr = document.querySelector(`tr[data-track-id="${trackId}"]`);
            if (tr) {
                const tds = tr.querySelectorAll('td');
                const platformIndex = platform === 'tidal' ? 7 : 8;
                const td = tds[platformIndex];
                if (td) {
                    const inputHtml = platform === 'tidal'
                        ? `<input type="text" placeholder="Tidal URL" style="width:100px;font-size:10px;" onchange="pageController.trackProblematic.updateUrl(${trackId}, 'tidal', this.value)">`
                        : `<input type="text" placeholder="Spotify URL" style="width:100px;font-size:10px;" onchange="pageController.trackProblematic.updateUrl(${trackId}, 'spotify', this.value)">`;
                    td.innerHTML = inputHtml;
                }
            }
        }
    }

    async bulkUpdate() {
        const checkboxes = document.querySelectorAll('.track-checkbox:checked');
        const ids = Array.from(checkboxes).map(cb => parseInt(cb.value));
        const status = parseInt(document.getElementById('bulkStatus').value);

        if (ids.length === 0) return;

        try {
            loader(1);

            const response = await fetch('/v1/stats/track-problematic/status', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ loTrackId: ids, status: status })
            });

            if (!response.ok) {
                const errText = await response.text();
                throw new Error(`Ошибка обновления статуса: ${response.status} ${response.statusText} ${errText}`);
            }

            location.reload();
        } catch (error) {
            console.error('Ошибка:', error);
        } finally {
            loader(0);
        }
    }

}
