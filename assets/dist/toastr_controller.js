import { Controller } from '@hotwired/stimulus'
import toastr from 'toastr'
import 'toastr/build/toastr.min.css'

export default class extends Controller {

    connect() {
        var data = JSON.parse(this.element.dataset.messages)
        var options = JSON.parse(this.element.dataset.options || '{}')
        if(options) {
            toastr.options = {...this.getDefaultOptions(), ...options}
        }
        // console.debug('Toastr options:', toastr.options)
        const const_delay = 200
        var delay = const_delay
        for (const type in data) {
            data[type].forEach(message => {
                setTimeout(() => toastr[type](message), delay)
                delay += const_delay
            });
        }
    }

    // disconnect() {
        
    // }

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