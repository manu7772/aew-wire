import { Controller } from '@hotwired/stimulus'
import toastr from 'toastr'
import 'toastr/build/toastr.min.css'

export default class extends Controller {

    toasts = []

    connect() {
        let data = JSON.parse(this.element.dataset.messages)
        let options = JSON.parse(this.element.dataset.options || '{}')
        if(options) {
            toastr.options = {...this.getDefaultOptions(), ...options}
        }
        // console.debug('Toastr options:', toastr.options)
        const const_delay = 200
        let delay = const_delay
        for (const type in data) {
            data[type].forEach(message => {
                setTimeout(() => {
                    const newtoast = toastr[type](message)
                    this.toasts.push(newtoast)
                }, delay)
                delay += const_delay
            });
        }
    }

    disconnect() {
        for (const toast of this.toasts) {
            toastr.remove(toast)
        }
    }

    getDefaultOptions = () => {
        const options = {
            escapeHtml: false,
            closeButton: true,
            timeOut: 5000, // How long the toast will display without user interaction
            extendedTimeOut: 700, // How long the toast will display after a user hovers over it
            closeDuration: 150,
            progressBar: true,
            positionClass: 'toast-top-right',
            preventDuplicates: true,
            showEasing: 'swing',
            hideEasing: 'linear',
            showMethod: 'fadeIn',
            hideMethod: 'fadeOut',          
        }
        return options
    }

}