import './wire-bootstrap.js'
// enable the interactive UI components from Flowbite
// import { initFlowbite } from 'flowbite'

/*
 * Welcome to your wireadmin's JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/wire.css'

// alert('Hello, wireadmin!')

// document.addEventListener('turbo:render', () => {
//     initFlowbite()
// })
// document.addEventListener('turbo:frame-render', () => {
//     initFlowbite()
// })
// Form: disable all fields on submit
document.addEventListener("turbo:submit-start", ({ target }) => {
    for (const field of target.elements) {
        field.disabled = true
    }
})
