document.addEventListener("alpine:init", () => {
    // Cart store with localStorage persistence
    const savedCart = localStorage.getItem("cart");
    const initialItems = savedCart ? JSON.parse(savedCart) : [];

    // Cart store definition
    Alpine.store("cart", {
        items: initialItems,
        save() {
            localStorage.setItem("cart", JSON.stringify(this.items));
        },
        get count() {
            return this.items.reduce((total, item) => total + item.qty, 0);
        },
        get total() {
            return this.items.reduce(
                (total, item) => total + item.price * item.qty,
                0,
            );
        },
        get groupedByShop() {
            const groups = {};
            this.items.forEach((item) => {
                const shopId = item.shop_id ?? "unknown";
                if (!groups[shopId]) {
                    groups[shopId] = {
                        shop_id: shopId,
                        shop_name: item.shop_name ?? "Toko",
                        items: [],
                    };
                }
                groups[shopId].items.push(item);
            });
            return Object.values(groups);
        },
        add(product) {
            let existing = this.items.find((i) => i.id === product.id);
            if (existing) {
                existing.qty += product.qty || 1;
            } else {
                this.items.push({ ...product, qty: product.qty || 1 });
            }
            this.save();
        },
        remove(id) {
            this.items = this.items.filter((i) => i.id !== id);
            this.save();
        },
        updateQty(id, qty) {
            let item = this.items.find((i) => i.id === id);
            if (item) {
                item.qty = Math.max(1, qty);
                this.save();
            }
        },
        clear() {
            this.items = [];
            this.save();
        },
    });
});
