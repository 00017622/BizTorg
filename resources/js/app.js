import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('DOMContentLoaded', function () {
    const attachFavoriteListeners = () => {
        const favoriteButtons = document.querySelectorAll('.favorite-btn');

        favoriteButtons.forEach(button => {
            button.removeEventListener('click', handleFavoriteToggle);

            button.addEventListener('click', handleFavoriteToggle);
        });
    };

    const handleFavoriteToggle = function (event) {
        event.stopPropagation();
        event.preventDefault();

        const productId = this.getAttribute('data-product-id');
        const productCard = this.closest('.flex');

        fetch('/favorites/toggle', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({ product_id: productId }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                if (data.isFavorited) {
                    this.classList.add('favorited');
                } else {
                    this.classList.remove('favorited');

                    if (window.location.pathname.includes('favorites')) {
                        productCard.remove();
                    }
                }
            } else {
                console.error('Failed to toggle favorite status');
            }
        })
        .catch(error => console.error('Error:', error));
    };

    attachFavoriteListeners();

    const dynamicContainer = document.querySelector('.dynamic-container');
    if (dynamicContainer) {
        const observer = new MutationObserver(() => {
            attachFavoriteListeners();
        });

        observer.observe(dynamicContainer, { childList: true, subtree: true });
    }
});
