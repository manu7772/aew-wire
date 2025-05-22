import { Controller } from '@hotwired/stimulus'
import { initFlowbite } from 'flowbite'
/* stimulusFetch: 'lazy' */
export default class extends Controller {

    // Darkmode
    darkmodeSwitchers = document.querySelectorAll('[data-darkmode-switcher]')
    classHolder = document.querySelector('[data-darkmode-switcher-url]')
    darkModeUrl = this.classHolder ? this.classHolder.getAttribute('data-darkmode-switcher-url') : null
    // Openers
    openers = document.querySelectorAll('.opener')
    togglers = document.querySelectorAll('.element-toggler')

    connect() {
        initFlowbite();
        // console.debug('Globalapp controller connected')
        const apddata = JSON.parse(this.element.getAttribute('data-app'))
        for (const switcher of this.darkmodeSwitchers) {
            if(!this.darkModeUrl) {
                // No URL so hide the switcher
                switcher.classList.add('hidden')
            } else {
                switcher.addEventListener('click', this.darkSwitcher)
            }
        }
        for (const opener of this.openers) {
            const submenu = document.querySelector('[aria-labelledby="' + opener.getAttribute('id') + '"]')
            if(submenu) {
                submenu.style.opacity = 0
                if (!submenu.classList.contains('hidden')) submenu.classList.add('hidden')
                opener.addEventListener('click', this.openOpener)
                opener.addEventListener('focusin', this.openOpener)
                opener.addEventListener('focusout', this.closeOpener)
            }
        }
        for (const toggler of this.togglers) {
            const toggledElement = document.querySelector('#' + toggler.getAttribute('aria-controls'))
            if (toggledElement) {
                const toggle_trigger = toggler.getAttribute('data-toggle-trigger') || 'click'
                toggler.addEventListener(toggle_trigger, this.toggleItem)
            }
        }
        // console.debug('Globalapp controller data', apddata)
        // console.info('*** Darkmode Switchers', this.darkmodeSwitchers)
        // console.info('*** Darkmode Class Holder', this.classHolder)
        // console.info('*** Darkmode URL', this.darkModeUrl)
    
    }

    disconnect() {
        for (const switcher of this.darkmodeSwitchers) {
            if(!this.darkModeUrl) {
                // nothing
            } else {
                switcher.removeEventListener('click', this.darkSwitcher)
            }
        }
        for (const opener of this.openers) {
            const submenu = document.querySelector('[aria-labelledby="' + opener.getAttribute('id') + '"]')
            if(submenu) {
                submenu.style.opacity = 0
                submenu.classList.add('hidden')
                opener.removeEventListener('click', this.openOpener)
                opener.removeEventListener('focusin', this.openOpener)
                opener.removeEventListener('focusout', this.closeOpener)
            }
        }
        for (const toggler of this.togglers) {
            const toggledElement = document.querySelector('#' + toggler.getAttribute('aria-controls'))
            if (toggledElement) {
                const toggle_trigger = toggler.getAttribute('data-toggle-trigger') || 'click'
                toggler.removeEventListener(toggle_trigger, this.toggleItem)
            }
        }
    }

    setDarkmode = (dm) => {
        if(!this.classHolder) return
        if (dm && !this.classHolder.classList.contains('dark')) this.classHolder.classList.add('dark')
        if (!dm && this.classHolder.classList.contains('dark')) this.classHolder.classList.remove('dark')
    }

    toggleDarkmode = () => {
        if(!this.classHolder) return
        this.classHolder.classList.toggle('dark')
    }

    darkSwitcher = (event) => {
        this.toggleDarkmode()
        if(!this.darkModeUrl) {
            // No URL but we can still toggle the class
            return
        }
        const headers = new Headers()
        headers.append('Content-Type', 'application/json')
        fetch(this.darkModeUrl, { headers: headers })
            .then((response) => response.json())
            .then((response) => {
                this.setDarkmode(response.darkmode)
            })
            .catch((error) => {
                console.error(error)
            })
    }

    openOpener = (event) => {
        const opener = event.target.closest('.opener')
        const submenu = document.querySelector('[aria-labelledby="' + opener.getAttribute('id') + '"]')
        submenu.classList.remove('hidden')
        submenu.style.animation = 'showit 1s cubic-bezier(0, 0, 0.2, 1)'
        submenu.style.opacity = 1
    }

    closeOpener = (event) => {
        const opener = event.target.closest('.opener')
        const submenu = document.querySelector('[aria-labelledby="' + opener.getAttribute('id') + '"]')
        submenu.style.animation = 'hideit .3s cubic-bezier(0, 0, 0.2, 1)'
        submenu.style.opacity = 0
        setTimeout(() => { submenu.classList.add('hidden') }, 500)
    }

    toggleItem = (event) => {
        const toggler = event.target.closest('.element-toggler')
        const toggleClass = toggler.getAttribute('data-toggle-class') || 'hidden'
        const childToggles = toggler.querySelectorAll('[child-toggle]')
        const toggledElement = document.querySelector('#' + toggler.getAttribute('aria-controls'))
        if(toggledElement) {
            toggledElement.classList.toggle(toggleClass)
            for (const $childToggle of childToggles) {
                $childToggle.classList.toggle(toggleClass)
            }
        }
    }

}