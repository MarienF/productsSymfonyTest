import './bootstrap.js';
import './styles/app.css';

function showSuccessMessage(message) {
    const alertSuccess = document.createElement('div');
    alertSuccess.className = 'alert-success';
    alertSuccess.textContent = message;
    document.querySelector('.container').insertBefore(alertSuccess, document.querySelector('.search-container'));

    setTimeout(() => {
        alertSuccess.remove();
    }, 5000);
}

function showErrorMessage(message) {
    const alertError = document.createElement('div');
    alertError.className = 'alert-error';
    alertError.textContent = message;
    document.querySelector('.container').insertBefore(alertError, document.querySelector('.search-container'));
}

// ADD
function attachAddListeners() {
    document.getElementById('btn-add').addEventListener('click', function(e) {
            e.preventDefault();
            const modal = document.getElementById('updateModal');
            const productCard = null;
            const id = 0;

            // Initialiser la modale
            fillModalWithProductData(productCard);

            // Afficher et gérer la modale
            handleModal(modal, productCard, id);
        });
}


// DELETE
function attachDeleteListeners() {
    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')) return;

            const id = this.getAttribute('data-product-id');
            fetch(`/api/products/delete/${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.closest('.product-card').remove();
                        showSuccessMessage('Produit supprimé avec succès !');
                    } else {
                        showErrorMessage(data.error);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showErrorMessage('Erreur lors de la suppression du produit');
                });
        });
    });
}


// UPDATE
function attachEditListeners() {
    document.querySelectorAll('.btn-edit').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const modal = document.getElementById('updateModal');
            const productCard = this.closest('.product-card');
            const id = this.getAttribute('data-product-id');

            // Remplir la modale
            fillModalWithProductData(productCard);
            
            // Afficher et gérer la modale
            handleModal(modal, productCard, id);
        });
    });
}


// FONCTIONS
function fillModalWithProductData(productCard) {
    if (!productCard) {
        document.getElementById('modalName').value = ''
        document.getElementById('modalPrice').value = ''
        document.getElementById('modalDescription').value = ''
    } else {
        document.getElementById('modalName').value = productCard.querySelector('h3').textContent;
        document.getElementById('modalPrice').value = productCard.querySelector('p').textContent.split(':')[1].trim().replace('€', '');
        document.getElementById('modalDescription').value = productCard.querySelectorAll('p')[1].textContent;
    }
}

function handleModal(modal, productCard, id) {
    modal.style.display = 'block';

    if (id == 0) {document.getElementById('titreModal').textContent = 'Ajouter un produit'} else {document.getElementById('titreModal').textContent = 'Modifier un produit'}

    // Gestion de la fermeture
    document.querySelector('.close').onclick = () => modal.style.display = 'none';
    window.onclick = (event) => {
        if (event.target == modal) modal.style.display = 'none';
    };

    // Gestion de la sauvegarde
    document.getElementById('saveButton').onclick = () => handleSave(modal, productCard, id);
}

function handleSave(modal, productCard, id) {
    // recuperation des valeurs
    const name = document.getElementById('modalName').value;
    const price = parseFloat(document.getElementById('modalPrice').value);
    const description = document.getElementById('modalDescription').value;

    // Validation
    let errors = [];
    if (name.length < 3) {
        errors.push("Le nom doit faire au moins 3 caractères");
    }
    if (price <= 0) {
        errors.push("Le prix doit être positif");
    }
    if (description.length < 10) {
        errors.push("La description doit faire au moins 10 caractères");
    }
    if (description.length > 255) {
        errors.push("La description doit faire moins de 255 caractères");
    }

    // S'il y a des erreurs, on les affiche et on arrête
    if (errors.length > 0) {
        showErrorMessage(errors.join('\n'));
        return;
    }

    // Si tout est valide, on continue avec la mise à jour
    const updatedData = { name, price, description };

    if (id != 0) {
        fetch(`/api/products/update/${id}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(updatedData)
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                updateProductCard(productCard, data.product);
                modal.style.display = 'none';
                showSuccessMessage('Produit modifié avec succès !');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showErrorMessage('Erreur lors de la modification du produit');
        });
    } else {
        fetch(`/api/products/add`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(updatedData)
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                addProductCard(data.product);
                modal.style.display = 'none';
                showSuccessMessage('Produit ajouté avec succès !');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showErrorMessage('Erreur lors de l\'ajout du produit');
        });
    }
}

function updateProductCard(productCard, data) {
    productCard.querySelector('h3').textContent = data.name;
    productCard.querySelector('p').textContent = `Prix : ${data.price}€`;
    productCard.querySelectorAll('p')[1].textContent = data.description;
}

function addProductCard(data) {
    // Créer la nouvelle card
    const newProductHTML = `
        <div class="product-card">
            <div class="product-info">
                <h3>${data.name}</h3>
                <p>Prix : ${data.price}€</p>
                <p>${data.description}</p>
            </div>
            <div class="product-actions">
                <a class="btn-edit" data-product-id="${data.id}">Modifier</a>
                <a class="btn-delete" data-product-id="${data.id}">Supprimer</a>
            </div>
        </div>
    `;

    // Ajouter la card à la fin du conteneur
    const productsContainer = document.querySelector('.products-container');
    productsContainer.insertAdjacentHTML('beforeend', newProductHTML)
}


// SEARCH
document.addEventListener('DOMContentLoaded', function() {
    attachAddListeners();
	attachDeleteListeners();
    attachEditListeners();
	
    const searchInput = document.querySelector('.search-input');
    const productsContainer = document.querySelector('.products-container');
    let timeoutId;

    searchInput.addEventListener('input', function(e) {
        clearTimeout(timeoutId);
        
        // Debounce pour éviter trop de requêtes
        timeoutId = setTimeout(() => {
            const searchTerm = e.target.value;
            
            fetch(`/api/products/search?search=${encodeURIComponent(searchTerm)}`)
                .then(response => response.json())
                .then(products => {
                    productsContainer.innerHTML = products.map(product => `
                        <div class="product-card">
                            <div class="product-info">
                                <h3>${product.name}</h3>
                                <p>Prix : ${product.price}€</p>
                                <p>${product.description}</p>
                            </div>
                            <div class="product-actions">
                                <a class="btn-edit" data-product-id="${product.id}">
                                    Modifier
                                </a>
                                <a class="btn-delete" data-product-id="${product.id}">
                                    Supprimer
                                </a>
                            </div>
                        </div>
                    `).join('');

					// Réattacher les écouteurs après avoir créé les nouveaux éléments
					attachDeleteListeners();
					attachEditListeners();
                })
                .catch(error => console.error('Erreur:', error));
        }, 300); // Attendre 300ms après la dernière frappe
    });
});