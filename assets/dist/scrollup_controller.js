import { Controller } from '@hotwired/stimulus'

/* stimulusFetch: 'lazy' */
export default class extends Controller {

    static values = {
        startAt: Number,
    }

    connect() {
        this.showScroll()
        window.addEventListener("scroll", this.showScroll)
        this.element.addEventListener('click', this.rollUp)
    }

    disconnect() {
        window.removeEventListener("scroll", this.showScroll)
        this.element.removeEventListener('click', this.rollUp)
    }

    visible = () => {
        return window.scrollY > (this.startAtValue || 300)
    }

    rollUp = () => {
        window.scrollTo({
            top: 0,
            left: 0,
            behavior: "smooth",
        })
        this.element.blur()
    }

    showScroll = () => {
        if(this.visible()) {
            // this.element.classList.remove('hidden')
            this.element.style.display = 'inherit'
        } else {
            // if(!this.element.classList.contains('hidden')) this.element.classList.add('hidden')
            this.element.style.display = 'none'
        }
    }

}
