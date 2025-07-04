import { Controller } from '@hotwired/stimulus'
import { initFlowbite } from 'flowbite'
/* stimulusFetch: 'lazy' */
export default class extends Controller {

    // Csstheme
    cssthemeSwitchers = document.querySelectorAll('[data-csstheme-switcher]')
    classHolder = document.querySelector('[data-csstheme-switcher-url]')
    cssthemeUrl = this.classHolder ? this.classHolder.getAttribute('data-csstheme-switcher-url') : null
    themes_choices = this.classHolder ? (this.classHolder.dataset.cssthemes ? JSON.parse(this.classHolder.dataset.cssthemes) : null) : null
    hasDataTheme = this.classHolder ? this.classHolder.hasAttribute('data-theme') : false
    // Modal confirm
    modalConfirms = document.querySelectorAll('[data-modal-confirm]')

    connect() {
        initFlowbite();
        const apddata = JSON.parse(this.element.getAttribute('data-app'))
        for (const switcher of this.cssthemeSwitchers) {
            if(!this.cssthemeUrl) {
                // No URL so hide the switcher
                switcher.classList.add('hidden')
            } else {
                switcher.addEventListener('click', this.cssthemeSwitcher)
            }
        }
        for (const modalConf of this.modalConfirms) {
            modalConf.addEventListener('click', this.modalConfirm)
            modalConf.addEventListener('submit', this.modalConfirm)
        }    
    }

    disconnect() {
        for (const switcher of this.cssthemeSwitchers) {
            if(!this.cssthemeUrl) {
                // nothing
            } else {
                switcher.removeEventListener('click', this.cssthemeSwitcher)
            }
        }
        for (const modalConf of this.modalConfirms) {
            modalConf.removeEventListener('click', this.modalConfirm)
            modalConf.removeEventListener('submit', this.modalConfirm)
        }
    }

    toggleCsstheme = () => {
        if(!this.classHolder || !this.themes_choices) return
        const id = this.themes_choices.indexOf(this.classHolder.dataset.theme)
        const next_id = id >= 0 ? (id + 1) % this.themes_choices.length : 0
        const newTheme = this.themes_choices[next_id]
        this.defineCsstheme(newTheme)
    }

    defineCsstheme = (csstheme) => {
        if(!this.classHolder) return
        if(this.themes_choices) {
            for (const theme of this.themes_choices) {
                this.classHolder.classList.remove(theme)
            }
        }
        this.classHolder.classList.add(csstheme)
        this.classHolder.dataset.theme = csstheme
    }

    cssthemeSwitcher = (event) => {
        event.preventDefault()
        const elem = event.target.closest('[data-csstheme-switcher]')
        if(elem.dataset.cssthemeDefine) {
            // Define a specific csstheme
            this.defineCsstheme(elem.dataset.cssthemeDefine)
        } else {
            // Toggle the csstheme
            this.toggleCsstheme()
        }
        if(!this.cssthemeUrl) {
            // No URL but we can still toggle the class
            return
        }
        const headers = new Headers()
        headers.append('Content-Type', 'application/json')
        fetch(this.cssthemeUrl, { headers: headers })
            .then((response) => response.json())
            .then((response) => {
                // console.debug('Csstheme response:', response.csstheme)
                this.defineCsstheme(response.csstheme)
            })
            .catch((error) => {
                console.error(error)
            })
    }

    modalConfirm = (event) => {
        event.preventDefault()
        const main = event.target.closest('[data-modal-confirm]')
        const modal_id = main.getAttribute('data-modal-target')
        // console.debug('Modal confirm triggered for:', modal_id, main)
        const the_modal = FlowbiteInstances.getInstance('Modal', modal_id);
        if(the_modal) {
            if(the_modal.isHidden()) {
                the_modal.show() 
            }
            console.debug('Modal confirm instance found:', the_modal)
            switch (true) {
                case ['FORM'].includes(main.nodeName):
                    const form_triggers = the_modal._targetEl.querySelectorAll('[data-modal-confirm-trigger]')
                    for (const trigger of form_triggers) {
                        trigger.addEventListener('click', (e) => {
                            the_modal.destroyAndRemoveInstance()
                            main.submit()
                        })
                    }
                    break;
                case ['BUTTON', 'A'].includes(main.nodeName):
                    const url = main.getAttribute('href') || main.getAttribute('data-url')
                    if(url) {
                        const abutton_triggers = the_modal._targetEl.querySelectorAll('[data-modal-confirm-trigger]')
                        for (const trigger of abutton_triggers) {
                            trigger.addEventListener('click', (e) => {
                                the_modal.destroyAndRemoveInstance()
                                window.location.href = url
                            })
                        }
                    } else {
                        console.warn('No URL found for modal confirm with BUTTON or A. Please provide a valid URL.')
                    }
                    break;
                default:
                    break;
            }
        }
    }

    // Remove and destroy all Flowbite instances
    destroyAllFlowbiteInstances = () => {
        for (const [name, instances] of Object.entries(FlowbiteInstances.getAllInstances())) {
            for (const [instance_id, instance] of Object.entries(instances)) {
                // console.debug('*** Destroying Flowbite instance ' + instance_id + ':', instance)
                FlowbiteInstances.destroyAndRemoveInstance(instance_id)
            }
        }
    }

}