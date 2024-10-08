import { Controller } from "@hotwired/stimulus"
import DataTable from 'datatables.net-dt'

console.debug('Controller DataTable enabled')
let isDataTableInitialized = false
class default_1 extends Controller
{
    static targets = ['viewValue']

    connect() {
        console.debug('Creating new DataTable', this.viewValue)
        if(!isDataTableInitialized) {
            const payload = this.viewValue;
            new DataTable(this.element, payload)
            isDataTableInitialized = true
        }
    }

}

default_1.values = {
    view: Object,
}

export { default_1 as default }