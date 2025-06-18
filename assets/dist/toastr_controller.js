import { Controller } from '@hotwired/stimulus'
import toastr from 'toastr'
import 'toastr/build/toastr.min.css'

/**
 * @see https://github.com/CodeSeven/toastr
 * @see http://www.toastrjs.com/
 */
export default class extends Controller {

    /** @see https://stimulus.hotwired.dev/reference/targets */
    static targets = ['toastr'] // use: data-aequation--wire--toastr-target="toastr"

    toasts = []

    toastrTargetConnected(element) {
        this.clearToasts()
        const data = element.dataset.messages
        if(data) {
            const messages = JSON.parse(data)
            const options = element.dataset.options
            // console.debug('Toastr messages target connected:', messages)
            toastr.options = options
                ? {...toastr.options, ...this.getDefaultOptions(), ...JSON.parse(options)}
                : {...toastr.options, ...this.getDefaultOptions()}
            const const_delay = 200
            let delay = const_delay
            for (const type in messages) {
                messages[type].forEach(message => {
                    setTimeout(() => {
                        const newtoast = toastr[type](message)
                        this.toasts.push(newtoast)
                        // setTimeout(() => { toastr.remove(newtoast) }, 500)
                    }, delay)
                    delay += const_delay
                });
            }
        }
    }

    // toastrTargetDisconnected(element) {
    //     this.clearToasts()
    // }

    clearToasts = () => {
        for (const toast of this.toasts) {
            toastr.remove(toast)
        }
        this.toasts = [] // Clear the toasts array
    }

    getDefaultOptions = () => {
        return {
            escapeHtml: false,
            closeButton: true,
            timeOut: 4000, // How long the toast will display without user interaction
            extendedTimeOut: 500, // How long the toast will display after a user hovers over it
            closeDuration: 150,
            progressBar: true,
            positionClass: 'toast-top-right',
            preventDuplicates: true,
            showEasing: 'swing',
            hideEasing: 'linear',
            showMethod: 'fadeIn',
            hideMethod: 'fadeOut',          
        }
    }

}