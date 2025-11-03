import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.fileInput = this.element.querySelector('input[type="file"]');
        
        // Prevent multiple initializations
        if (this.element.dataset.imagePreviewInitialized) {
            return;
        }
        this.element.dataset.imagePreviewInitialized = 'true';
        
        this.setupFileInput();
        this.setupDragAndDrop();
        this.setupClickToUpload();
    }

    setupFileInput() {
        if (this.fileInput && !this.fileInput.dataset.hasChangeListener) {
            this.fileInput.dataset.hasChangeListener = 'true';
            this.fileInput.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    this.handleFileSelection(e.target.files);
                }
            });
        }
    }

    setupClickToUpload() {
        const dropZone = this.element.querySelector('#dropZone');
        if (dropZone && this.fileInput && !dropZone.dataset.hasClickListener) {
            dropZone.dataset.hasClickListener = 'true';
            dropZone.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.fileInput.click();
            });
        }
    }

    disconnect() {
        // Clean up when controller is disconnected
        if (this.element) {
            delete this.element.dataset.imagePreviewInitialized;
        }
        if (this.fileInput) {
            delete this.fileInput.dataset.hasChangeListener;
        }
    }

    setupDragAndDrop() {
        const dropZone = this.element.querySelector('#dropZone');
        if (dropZone) {
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.add('border-[#B32224]', 'bg-red-50');
            });

            dropZone.addEventListener('dragleave', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.remove('border-[#B32224]', 'bg-red-50');
            });

            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
                dropZone.classList.remove('border-[#B32224]', 'bg-red-50');
                
                if (this.fileInput && e.dataTransfer.files.length > 0) {
                    // Set the files directly to the input
                    this.fileInput.files = e.dataTransfer.files;
                    // Trigger change event to show previews
                    const event = new Event('change', { bubbles: true });
                    this.fileInput.dispatchEvent(event);
                }
            });
        }
    }

    handleFileSelection(files) {
        const previewContainer = this.element.querySelector('#imagePreviewContainer');
        
        // Clear only new previews (keep existing images)
        const newPreviews = previewContainer.querySelectorAll('.preview-new');
        newPreviews.forEach(preview => preview.remove());
        
        // Show preview for new files
        Array.from(files).forEach((file) => {
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const preview = document.createElement('div');
                    preview.className = 'relative group preview-new';
                    preview.dataset.fileName = file.name;
                    preview.innerHTML = `
                        <img src="${e.target.result}" 
                             alt="New image" 
                             class="w-full h-32 object-cover rounded-lg">
                        <button type="button"
                        class="remove-preview-btn absolute -top-1 -right-1 bg-white hover:bg-red-700 text-black hover:text-white rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold transition-colors duration-200 opacity-0 group-hover:opacity-100"> 
                                X
                        </button>
                    `;
                    
                    // Add click handler for remove button
                    const removeBtn = preview.querySelector('.remove-preview-btn');
                    removeBtn.addEventListener('click', () => {
                        this.removePreviewImage(preview, file.name);
                    });
                    
                    previewContainer.appendChild(preview);
                };
                reader.readAsDataURL(file);
            }
        });
    }

    removePreviewImage(previewElement, fileName) {
        // Remove from preview
        previewElement.remove();
        
        // Rebuild the file list without this file
        const dt = new DataTransfer();
        const currentFiles = Array.from(this.fileInput.files);
        
        currentFiles.forEach(file => {
            if (file.name !== fileName) {
                dt.items.add(file);
            }
        });
        
        this.fileInput.files = dt.files;
    }
}
