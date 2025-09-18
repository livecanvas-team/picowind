import './styles/main.css';

import.meta.glob(
  [
    './styles/**/*.css',
    '!./styles/main.css',
    '!./styles/editor-style.css'
  ],
  { eager: true }
);

import.meta.glob(['../blocks/**/*.css'], { eager: true });
import.meta.glob('../blocks/**/*.js', { eager: true });

import Alpine from 'alpinejs';
window.Alpine = Alpine;
window.Alpine.start();