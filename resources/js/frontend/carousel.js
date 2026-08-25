/**
 * Reusable Alpine.js carousel data component.
 * Usage: x-data="carousel({ perViewSm: 1, perViewMd: 2, perViewLg: 3 })"
 */
export default function registerCarousel(Alpine) {
    Alpine.data('carousel', (config = {}) => ({
        perViewSm: config.perViewSm ?? 1,
        perViewMd: config.perViewMd ?? 2,
        perViewLg: config.perViewLg ?? 3,
        gap: config.gap ?? 32,
        autoplay: config.autoplay ?? false,
        interval: config.interval ?? 5000,
        loop: config.loop ?? false,

        current: 0,
        perView: 1,
        slideCount: 0,
        slideWidth: 0,
        autoplayTimer: null,

        get maxIndex() {
            return Math.max(0, this.slideCount - this.perView);
        },

        get showControls() {
            return this.slideCount > this.perView;
        },

        get dotCount() {
            return this.maxIndex + 1;
        },

        get trackStyle() {
            const translateX = this.current * (this.slideWidth + this.gap);
            return {
                transform: `translateX(-${translateX}px)`,
                gap: `${this.gap}px`,
            };
        },

        init() {
            this.$nextTick(() => {
                this.countSlides();
                this.updatePerView();
                this.recalculate();

                if (this.autoplay && this.showControls) {
                    this.startAutoplay();
                }
            });

            this._onResize = () => {
                this.updatePerView();
                this.recalculate();
                if (this.current > this.maxIndex) {
                    this.current = this.maxIndex;
                }
            };
            window.addEventListener('resize', this._onResize);
        },

        destroy() {
            this.stopAutoplay();
            window.removeEventListener('resize', this._onResize);
        },

        countSlides() {
            const track = this.$refs.track;
            if (!track) {
                this.slideCount = 0;
                return;
            }
            const slides = Array.from(track.children).filter((el) => el.nodeType === 1);
            this.slideCount = slides.length;
        },

        updatePerView() {
            const width = window.innerWidth;
            if (width >= 1024) {
                this.perView = this.perViewLg;
            } else if (width >= 768) {
                this.perView = this.perViewMd;
            } else {
                this.perView = this.perViewSm;
            }
        },

        recalculate() {
            const viewport = this.$refs.viewport;
            const track = this.$refs.track;
            if (!viewport || !track) return;

            const viewportWidth = viewport.offsetWidth;
            this.slideWidth = (viewportWidth - this.gap * (this.perView - 1)) / this.perView;

            Array.from(track.children).forEach((child) => {
                if (child.nodeType !== 1) return;
                child.style.flex = `0 0 ${this.slideWidth}px`;
                child.style.width = `${this.slideWidth}px`;
                child.style.maxWidth = `${this.slideWidth}px`;
            });
        },

        next() {
            if (this.current < this.maxIndex) {
                this.current += 1;
            } else if (this.loop) {
                this.current = 0;
            }
            this.resetAutoplay();
        },

        prev() {
            if (this.current > 0) {
                this.current -= 1;
            } else if (this.loop) {
                this.current = this.maxIndex;
            }
            this.resetAutoplay();
        },

        goTo(index) {
            this.current = Math.max(0, Math.min(index, this.maxIndex));
            this.resetAutoplay();
        },

        startAutoplay() {
            this.stopAutoplay();
            if (!this.autoplay || !this.showControls) return;

            this.autoplayTimer = setInterval(() => {
                if (this.current >= this.maxIndex) {
                    if (this.loop) {
                        this.current = 0;
                    } else {
                        this.stopAutoplay();
                    }
                } else {
                    this.current += 1;
                }
            }, this.interval);
        },

        stopAutoplay() {
            if (this.autoplayTimer) {
                clearInterval(this.autoplayTimer);
                this.autoplayTimer = null;
            }
        },

        resetAutoplay() {
            if (this.autoplay && this.showControls) {
                this.startAutoplay();
            }
        },
    }));
}
