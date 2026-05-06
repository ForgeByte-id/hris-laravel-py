self.addEventListener('install', event => {
    console.log('Service Worker installed');
});

self.addEventListener('fetch', event => {
    // optional caching logic
});
