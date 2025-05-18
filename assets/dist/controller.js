import { Controller } from "@hotwired/stimulus"
import { DataTable } from 'simple-datatables'

// let isDataTableInitialized = false
let datatable = null
class default_1 extends Controller
{
    static targets = ['viewValue']

    initialize() {
        console.debug('--- Initializing DataTable', this.viewValue)
    }

    connect() {
        if(!datatable) {
            // this.element.innerHtml = '<strong>Initialize......</strong>'
            // console.debug('--- Creating DataTable', [this.element, this.viewValue])
            const payload = this.viewValue;
            datatable = new DataTable(this.element, payload)
        } else {
            // console.debug('--- Updating DataTable', [this.element, this.viewValue])
            datatable.refresh()
        }
    }

    disconnect() {
        // console.debug('--- Stopped datatable', this.viewValue)
        // if(datatable) {
        //     datatable.destroy()
        //     datatable = null
        // }
    }

}

default_1.values = {
    view: Object,
}

export { default_1 as default }