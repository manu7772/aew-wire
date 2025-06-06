import { Controller } from '@hotwired/stimulus'
import { initFlowbite } from 'flowbite'
/* stimulusFetch: 'lazy' */
export default class extends Controller {

    // Darkmode
    darkmodeSwitchers = document.querySelectorAll('[data-darkmode-switcher]')
    classHolder = document.querySelector('[data-darkmode-switcher-url]')
    darkModeUrl = this.classHolder ? this.classHolder.getAttribute('data-darkmode-switcher-url') : null
    // Modal confirm
    modalConfirms = document.querySelectorAll('[data-modal-confirm]')

    connect() {
        initFlowbite();
        const apddata = JSON.parse(this.element.getAttribute('data-app'))
        for (const switcher of this.darkmodeSwitchers) {
            if(!this.darkModeUrl) {
                // No URL so hide the switcher
                switcher.classList.add('hidden')
            } else {
                switcher.addEventListener('click', this.darkSwitcher)
            }
        }
        for (const modalConf of this.modalConfirms) {
            modalConf.addEventListener('click', this.modalConfirm)
            modalConf.addEventListener('submit', this.modalConfirm)
        }    
    }

    disconnect() {
        for (const switcher of this.darkmodeSwitchers) {
            if(!this.darkModeUrl) {
                // nothing
            } else {
                switcher.removeEventListener('click', this.darkSwitcher)
            }
        }
        for (const modalConf of this.modalConfirms) {
            modalConf.removeEventListener('click', this.modalConfirm)
            modalConf.removeEventListener('submit', this.modalConfirm)
        }
    }

    setDarkmode = (dm) => {
        if(!this.classHolder) return
        if (dm && !this.classHolder.classList.contains('dark')) this.classHolder.classList.add('dark')
        if (!dm && this.classHolder.classList.contains('dark')) this.classHolder.classList.remove('dark')
        console.debug('Darkmode set to:', dm, this.classHolder.classList.contains('dark') ? 'dark' : '(light)')
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
                console.debug('Darkmode response:', response.darkmode)
                this.setDarkmode(response.darkmode)
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