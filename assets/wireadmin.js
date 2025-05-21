import './wire-bootstrap.js'
// enable the interactive UI components from Flowbite
import { initFlowbite } from 'flowbite'

/*
 * Welcome to your wireadmin's JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
// import './styles/pico.css'
// import './styles/pico.colors.css'
import './styles/wire.css'
// import './fontawesome-free-6.5.1-web/css/all.min.css'
// import './styles/video.css'

// alert('Hello, wireadmin!')

document.addEventListener('turbo:render', () => {
    initFlowbite()
})
document.addEventListener('turbo:frame-render', () => {
    initFlowbite()
})
// Form: disable all fields on submit
document.addEventListener("turbo:submit-start", ({ target }) => {
    for (const field of target.elements) {
        field.disabled = true
    }
})
