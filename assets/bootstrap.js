import { startStimulusApp } from '@symfony/stimulus-bundle';
import wireadmin_controller from './dist/wireadmin_controller'

const app = startStimulusApp();
// register any custom, 3rd party controllers here
app.register('wireadmin', wireadmin_controller);
