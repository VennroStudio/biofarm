class TidalUI {

    LOG_ENABLED = true;

    STEP_PARSE_ARTIST = 0;
    STEP_PARSE_ALBUMS = 1;
    STEP_PARSE_TRACKS = 2;

    IS_ALBUMS = false;

    constructor() {

        let that = this;

        setTimeout(async function () {

            await that.checkModalDownload();

            await that.start();
        }, 5000);
    }

    async start() {

        let isSuccess = await this.getArtist();

        if (!isSuccess) {
            await this.delay(30 * 1000);
            await this.start();
        }
    }

    async looper() {

        this.logger('LOOP! ');

        if (await this.isStep(this.STEP_PARSE_ARTIST) && this.isUrl('/artist/')) {

            this.logger('/artist/');

            this.scanArtist().then(async result => {
                if (result === 0) {
                    await this.done();
                } else if (result === -1) {
                    await this.wait();
                } else {

                    if (result === 1) {
                        this.IS_ALBUMS = true;
                        await this.setParsedAlbums();
                    } else {
                        this.IS_ALBUMS = false;
                        await this.setParsedSingles();
                    }

                    await this.setStep(this.STEP_PARSE_ALBUMS);
                    await this.wait('/view/pages/');
                }
            });

        } else if (await this.isStep(this.STEP_PARSE_ALBUMS) && this.isUrl('/view/pages/')) {

            this.logger('/view/pages/');

            this.openAlbum().then(async isSuccess => {
                if (isSuccess) {
                    await this.setStep(this.STEP_PARSE_TRACKS);
                    await this.wait('/album/');
                } else {
                    this.comeBack().then(async _ => {
                        await this.setStep(this.STEP_PARSE_ARTIST);
                        await this.wait('/artist/');
                    });
                }
            });

        } else if (await this.isStep(this.STEP_PARSE_TRACKS) && this.isUrl('/album/')) {

            this.logger('/album/');

            this.scanAlbum().then(async isSuccess => {
                if (isSuccess) {
                    this.comeBack(2).then(async _ => {
                        await this.setStep(this.STEP_PARSE_ALBUMS);
                        await this.wait('/view/pages/');
                    });
                } else {
                    await this.wait();
                }
            });
        }
    }

    async goToArtist(artistLinkId) {

        let linkTopTracks = $("h2:contains('Top Tracks')") ?? undefined;

        if (linkTopTracks !== undefined) {

            let href = $(linkTopTracks).parent().parent().children('div').eq(1).find('a')?.attr('href') ?? undefined;

            if (href.indexOf('artistId=' + artistLinkId) !== -1) {
                return true;
            }
        }

        $('#search-field-container').eq(0)[0].click();

        await this.delay(2500);

        let searchItems = $('div[data-test="search-items"]');

        if (searchItems.length === 0) {
            return false;
        }

        let linkElem = $(searchItems).find('article').eq(0).children('div').eq(1).find('a');

        if (linkElem === undefined) {
            return false;
        }

        linkElem.attr('href', '/artist/' + artistLinkId);
        linkElem[0].click();

        await this.delay(2500);

        return false;
    }

    async scanArtist() { this.logger('scanArtist');

        let linkAlbums = $("h2:contains('Albums')").eq(0).parent().parent().find('a').eq(0)[0] ?? undefined;
        let linkSingles = $("h2:contains('EP & Singles')").eq(0).parent().parent().find('a').eq(0)[0] ?? undefined;

        if (linkAlbums === undefined && linkSingles === undefined) {
            return -1;
        }

        if (linkAlbums !== undefined && !await this.isParsedAlbums()) {
            linkAlbums.click();

            return 1;
        }

        if (linkSingles !== undefined && !await this.isParsedSingles()) {
            linkSingles.click();

            return 2;
        }

        return 0;
    }

    async openAlbum() {this.logger('openAlbum');

        let offsetTop = 144;
        let lastIds = [];
        let countEqual = 0;

        document.getElementById('main').scrollTop = 0;
        await this.delay(250);

        let countElementsInRow = 5;
        let elementsSkip = 0;
        let lasRowIndex = 1;

        while (true) {

            let albums = $('main').find('div[data-track--content-type="album"]');
            let currentIds = [];

            let rowIndex = parseInt($('div[aria-rowindex]').eq(0).attr('aria-rowindex'));

            if (rowIndex !== lasRowIndex) {
                lasRowIndex = rowIndex;
                elementsSkip -= countElementsInRow;
            }

            for (let i = 0; i < albums.length; i++) {

                let parsedAlbumIds = await this.getParsedAlbumIds();

                let albumId = $(albums[i]).attr('data-track--content-id');
                currentIds.push(albumId);

                let count = i + 1;

                if (count % countElementsInRow === 0 && count > elementsSkip) {

                    let top = $(albums[i]).closest('div[data-type="cell"]').offset().top;

                    document.getElementById('main').scrollTop = offsetTop + top;
                    await this.delay(250);

                    if (i !== 0) {
                        offsetTop += top;
                    }

                    elementsSkip += countElementsInRow;
                    break;
                }

                if (!parsedAlbumIds.includes(albumId)) {
                    let link = $(albums[i]).find('a').eq(1);
                    link[0].click();

                    return true;
                }
            }

            if (this.arraysEqual(currentIds, lastIds)) {
                countEqual++;
            }

            if (countEqual > 5) {
                break;
            }

            lastIds = currentIds;
        }

        return false;
    }

    async comeBack(count) { this.logger('comeBack');

        if (count === undefined) {
            count = 1;
        }

        let btnBack = $('button[title="Back"]');

        for (let i = 0; i < count; i++) {
            btnBack.trigger('click');
            await this.delay(500);
        }
    }

    async scanAlbum() { this.logger('scanAlbum');

        try {
            let info = await this.scanAlbumInfo();

            $('button[data-track--button-id="credits"]').trigger('click');
            await this.delay(1500);

            let artistId = await this.getArtistId();
            let tracks = await this.scanAlbumTracks();

            let data = {
                artistId: artistId,
                albumId: info.albumId,
                isAlbum: info.isAlbum,
                name: info.name,
                photo: info.photo,
                photoAnimated: info.photoAnimated,
                cover: info.cover,
                coverAnimated: info.coverAnimated,
                description: info.description,
                artists: info.artists,
                releasedAt: info.releasedAt,
                label: info.label,
                attributes: info.attributes,
                tracks: tracks
            };

            let result = await fetch(API_URL + 'tidal/album', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'apiKey': API_KEY,
                },
                body: JSON.stringify(data)
            });

            result = await result.json();

            if (result?.data?.success === 1) {
                await this.addParsedAlbumId(data.albumId);
                this.logger(data);

                return true;
            }

        } catch (e) {}

        return false;
    }

    async scanAlbumInfo() { this.logger('scanAlbumInfo');

        let detailsBlock = $('.header-details').find('h2');

        let artists = [];

        let name = $(detailsBlock).html();
        let links = $(detailsBlock).parent().find('span').find('span').children('span').find('a');

        for (let l = 0; l < links.length; l++) {
            artists.push({
                title: $(links[l]).attr('title'),
                url: $(links[l]).attr('href'),
            });
        }

        let url = window.location.href;

        const parts = url.split("/");
        let albumId = (url.indexOf('/credits') !== -1) ? parts[parts.length - 2] : parts[parts.length - 1];

        return {
            albumId: albumId,
            isAlbum: this.IS_ALBUMS,
            name: name,
            photo: null, // автарка
            photoAnimated: null, // живая аватарка - https://listen.tidal.com/album/258373409/credits
            cover: null, // обложка
            coverAnimated: null, // обложка
            description: null, // вкладка infо (есть не у всех) - https://listen.tidal.com/album/302439809
            artists: artists,
            releasedAt: null, // дата релиза
            label: null, // лейбл
            attributes: [], // вкладка infо (есть не у всех) - https://listen.tidal.com/album/302439809
        };
    }

    async scanAlbumTracks() { this.logger('scanAlbumTracks');

        let offsetTop = 380;
        let tracks = [];
        let volumes = $('div[data-test="album-info-track-credits"]');

        document.getElementById('main').scrollTop = 0;
        await this.delay(250);

        for (let i = 0; i < volumes.length; i++) {
            let credits = $(volumes[i]).find('div[data-test="album-info-item"]');

            let trackNumber = 1;

            for (let k = 0; k < credits.length; k++) {

                let credit = $(credits[k]).find('div').find('div').find('div');
                let creditHead = credit.find('div').children('div').eq(1).children('div');
                let creditAttributes = credit.children('div').eq(1).children('div');

                if ((k + 1) % 3 === 0) {

                    let top = $(creditHead).offset().top;
                    let padding = $('main').children('div').eq(1).children('div').eq(0).find('div').height();

                    document.getElementById('main').scrollTop = offsetTop + top - padding;
                    await this.delay(150);

                    if (k === 0) {
                        offsetTop = padding;
                    } else {
                        offsetTop += top - padding;
                    }
                }

                let name = creditHead.eq(0).attr('title');
                let trackId = creditHead.eq(0).attr('data-test-id');
                let artists = creditHead.eq(1).find('div').attr('title').split(', ');
                let explicit = !!creditHead.eq(1).find('svg').length;

                // Получение ссылки на трек из кнопки "Радио по треку", но это есть не у всех (https://listen.tidal.com/album/321418055/credits)
                //let btn = credit.find('div').find('button[data-type="contextmenu-open"]');
                // btn.trigger('click');
                // await this.delay(200);

                // let urlSplit = $('li[data-track--icon-clicked="show_track_radio"]').find('a').attr('href').split('/');
                // let trackId = urlSplit[urlSplit.length - 2];

                // btn.trigger('click');
                // await this.delay(50);

                let attributes = [];

                for (let j = 0; j < creditAttributes.length; j++) {

                    let attributeTitle = $(creditAttributes[j]).children('span').eq(0).html();
                    let attributeLinks = [];

                    let links = $(creditAttributes[j]).children('span').eq(1).find('a');

                    for (let l = 0; l < links.length; l++) {
                        attributeLinks.push({
                            title: $(links[l]).attr('title'),
                            url: $(links[l]).attr('href'),
                        });
                    }

                    attributes.push({
                        title: attributeTitle,
                        links: attributeLinks
                    });
                }

                let track = {
                    diskNumber: i + 1,
                    trackNumber: trackNumber,
                    name: name,
                    artists: artists,
                    explicit: explicit,
                    trackId: parseInt(trackId),
                    attributes: attributes
                };

                tracks.push(track);
                trackNumber++;
            }
        }

        return tracks;
    }

    async done() {

        while (true) {
            let artistId = await this.getArtistId();

            let result = await fetch(API_URL + 'tidal/artist/' + artistId + '/done', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'apiKey': API_KEY,
                },
            });

            result = await result.json();

            let isSuccess = result?.data?.success;

            if (isSuccess) {
                break;
            }

            await this.delay(30 * 1000);
        }

        while (true) {
            let isSuccess = await this.getArtist();

            if (isSuccess) {
                break;
            }

            await this.delay(30 * 1000);
        }
    }

    async checkModalDownload() {
        let btnDownload = $('a').filter('.standalone-page-button')?.attr('href');

        if (btnDownload === 'https://tidal.com/download') {
            console.log('btnDownload!');
        }
    }

    isUrl(searchString) {
        let url = window.location.href;
        return url.indexOf(searchString) !== -1;
    }

    async wait(searchString) {

        this.logger('WAIT...');

        await this.delay(500);

        if (searchString === undefined) {
            await this.looper();
            return;
        }

        let that = this;

        let checker = setInterval(function () {
            if (that.isUrl(searchString)) {
                clearInterval(checker);
                that.looper();
            }
        }, 250);
    }

    async getArtist() {

        try {
            let result = await fetch(API_URL + 'tidal/artist', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'apiKey': API_KEY,
                }
            });

            result = await result.json();

            let artistId = result?.data?.id;
            let artistLinkId = 21102471;//result?.data?.artistId;

            let that = this;

            while (true) {
                if (await that.goToArtist(artistLinkId)) {
                    break;
                }

                await this.delay(1000);
            }

            await that.setArtistData(artistId);
            await that.looper();

            return true;

        } catch (e) {}

        return false;
    }

    async setArtistData(artistId) {

        await this.setArtistId(artistId);

        await this.resetParsedAlbums();
        await this.resetParsedSingles();
        await this.resetParsedAlbumIds();

        await this.setStep(this.STEP_PARSE_ARTIST);
    }

    async isActive() {
        let result = await chrome.storage.local.get(['isActive']);
        return (result.isActive !== undefined) ? result.isActive : false;
    }

    async isStep(step) {
        let currentStep = await this.getStep();

        return currentStep === step;
    }

    async getStep() {
        let result = await chrome.storage.local.get(['step']);
        return (result.step !== undefined) ? result.step : this.STEP_PARSE_ARTIST;
    }

    async setStep(step) {
        await chrome.storage.local.set({ step: step });
    }

    async getArtistId() {
        let result = await chrome.storage.local.get(['artistId']);
        return (result.artistId !== undefined) ? result.artistId : null;
    }

    async setArtistId(artistId) {
        await chrome.storage.local.set({ artistId: artistId });
    }

    async getParsedAlbumIds() {
        let result = await chrome.storage.local.get(['parsedAlbumIds']);
        return (result.parsedAlbumIds !== undefined) ? result.parsedAlbumIds : [];
    }

    async addParsedAlbumId(id) {
        let ids = await this.getParsedAlbumIds();

        ids.push(id);

        await chrome.storage.local.set({ parsedAlbumIds: ids });
    }

    async resetParsedAlbumIds() {
        await chrome.storage.local.set({ parsedAlbumIds: [] });
    }

    async isParsedAlbums() {
        let result = await chrome.storage.local.get(['parsedAlbums']);
        return (result.parsedAlbums !== undefined) ? result.parsedAlbums : false;
    }

    async setParsedAlbums() {
        await chrome.storage.local.set({ parsedAlbums: true });
    }

    async resetParsedAlbums() {
        await chrome.storage.local.set({ parsedAlbums: false });
    }

    async isParsedSingles() {
        let result = await chrome.storage.local.get(['parsedSingles']);
        return (result.parsedSingles !== undefined) ? result.parsedSingles : false;
    }

    async setParsedSingles() {
        await chrome.storage.local.set({ parsedSingles: true });
    }

    async resetParsedSingles() {
        await chrome.storage.local.set({ parsedSingles: false });
    }

    logger(text) {
        if (this.LOG_ENABLED) {
            console.log(text);
        }
    }

    delay(ms) {
        return new Promise((resolve) => setTimeout(resolve, ms));
    }

    arraysEqual(arr1, arr2) {
        if (arr1.length !== arr2.length) return false;
        for (let i = 0; i < arr1.length; i++) {
            if (arr1[i] !== arr2[i]) return false;
        }
        return true;
    }

}
