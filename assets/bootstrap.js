import { startStimulusApp } from '@symfony/stimulus-bundle';
import wireadmin_controller from './dist/wireadmin_controller.js'

const wireadmin = startStimulusApp();
// register any custom, 3rd party controllers here
wireadmin.register('wireadmin', wireadmin_controller);
