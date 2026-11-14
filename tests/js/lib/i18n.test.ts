import { afterEach, expect, test } from 'vite-plus/test';
import { currentLanguage, msg, setLanguage, t, tChoice } from '@/lib/i18n';

afterEach(() => {
    setLanguage('en', {});
});

test('English needs no table: a sentence is shown as it is', () => {
    setLanguage('en', {});

    expect(t('Save changes')).toBe('Save changes');
    expect(currentLanguage()).toBe('en');
});

test('a Chinese sentence replaces its English key, and a key with no entry shows as it is', () => {
    setLanguage('zh_CN', { 'Save changes': '保存更改' });

    expect(t('Save changes')).toBe('保存更改');
    expect(t('Something new')).toBe('Something new');
    expect(currentLanguage()).toBe('zh_CN');
});

test(':name placeholders are filled the way Laravel fills them', () => {
    setLanguage('zh_CN', { 'Delete :name': '删除 :name' });

    expect(t('Delete :name', { name: '报告' })).toBe('删除 报告');
    expect(t('Hello :name', { name: 'mei' })).toBe('Hello mei');
    expect(t(':Name is here', { name: 'mei' })).toBe('Mei is here');
    expect(t(':NAME is here', { name: 'mei' })).toBe('MEI is here');
    expect(t('Page :page of :pages', { page: 2, pages: 10 })).toBe(
        'Page 2 of 10',
    );
});

test('a longer placeholder name is not eaten by a shorter one', () => {
    expect(t(':total of :to', { to: 'b', total: 'a' })).toBe('a of b');
});

test('English plurals: one, or the rest', () => {
    const key = ':count task|:count tasks';

    expect(tChoice(key, 1)).toBe('1 task');
    expect(tChoice(key, 0)).toBe('0 tasks');
    expect(tChoice(key, 5)).toBe('5 tasks');
});

test('explicit counts and ranges, as in Laravel', () => {
    const key = '{0} No tasks|{1} One task|[2,*] :count tasks';

    expect(tChoice(key, 0)).toBe('No tasks');
    expect(tChoice(key, 1)).toBe('One task');
    expect(tChoice(key, 12)).toBe('12 tasks');
});

test('Chinese has one plural form, so every count takes it', () => {
    setLanguage('zh_CN', {
        ':count task|:count tasks': '共 :count 个任务',
    });

    expect(tChoice(':count task|:count tasks', 1)).toBe('共 1 个任务');
    expect(tChoice(':count task|:count tasks', 7)).toBe('共 7 个任务');
});

test('a choice takes extra values too', () => {
    expect(
        tChoice(
            'Delete :count task of :owner|Delete :count tasks of :owner',
            3,
            { owner: 'Mei' },
        ),
    ).toBe('Delete 3 tasks of Mei');
});

test('msg() marks a sentence to translate later and changes nothing', () => {
    setLanguage('zh_CN', { Dashboard: '仪表盘' });
    const item = { title: msg('Dashboard') };

    expect(item.title).toBe('Dashboard');
    expect(t(item.title)).toBe('仪表盘');
});
