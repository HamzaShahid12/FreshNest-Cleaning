import { defineStore } from 'pinia';

let nextId = 1;

export const useToastStore = defineStore('toast', {
    state: () => ({ items: [] }),
    actions: {
        push(message, type = 'success', timeout = 5000) {
            if (!message) return;
            const id = nextId++;
            this.items.push({ id, message, type });
            if (timeout) setTimeout(() => this.dismiss(id), timeout);
        },
        success(message) { this.push(message, 'success'); },
        error(message) { this.push(message, 'error', 8000); },
        dismiss(id) { this.items = this.items.filter((t) => t.id !== id); },
    },
});
