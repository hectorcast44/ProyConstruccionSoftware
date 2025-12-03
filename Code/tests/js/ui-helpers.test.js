const UIHelpers = require('../../public/assets/js/ui-helpers');

describe('UIHelpers', () => {
    describe('TagStyleManager', () => {
        test('should return a class for a given key', () => {
            const cls = UIHelpers.TagStyleManager.getClassFor('test');
            expect(typeof cls).toBe('string');
            expect(cls).toMatch(/^tag-/);
        });

        test('should return the same class for the same key', () => {
            const key = 'unique-key';
            const cls1 = UIHelpers.TagStyleManager.getClassFor(key);
            const cls2 = UIHelpers.TagStyleManager.getClassFor(key);
            expect(cls1).toBe(cls2);
        });

        test('should return different classes for different keys (up to palette size)', () => {
            const cls1 = UIHelpers.TagStyleManager.getClassFor('key1');
            const cls2 = UIHelpers.TagStyleManager.getClassFor('key2');
            expect(cls1).not.toBe(cls2);
        });

        test('should have document available', () => {
            const div = document.createElement('div');
            expect(div).toBeTruthy();
        });
    });
});
