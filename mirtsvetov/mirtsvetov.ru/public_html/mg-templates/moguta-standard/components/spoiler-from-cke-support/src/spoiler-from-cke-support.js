class Spoiler {
    constructor(spoilerElement) {
        this._spoiler = spoilerElement;
        this._spoilerTitle = this._spoiler.querySelector(`.spoiler-title`);
        this._spoilerContent = this._spoiler.querySelector(`.spoiler-content`);
        this._spolierIsClosed = true;
    }

    init() {
        this._spoilerTitle.addEventListener(`click`, this._slideToggleSpoilerContent.bind(this));
    }

    _slideToggleSpoilerContent() {
        if (this._spolierIsClosed) {
            this._spoilerContent.style.display = 'block';
            const _contentHeight = this._spoilerContent.scrollHeight;
            this._spoilerContent.style.height = `${_contentHeight}px`;
            this._spolierIsClosed = false;
        } else {
            this._spoilerContent.style.height = 0;
            this._spolierIsClosed = true;
        }
    }
}

document.querySelectorAll(`.spoiler`).forEach((element) => new Spoiler(element).init());
