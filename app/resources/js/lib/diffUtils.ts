/**
 * Генерирует diff в стиле git между двумя текстами
 * @param oldText - предыдущая версия текста
 * @param newText - новая версия текста
 * @returns diff в формате git
 */
export function generateGitStyleDiff(oldText: string, newText: string): string {
    const oldLines = oldText.split('\n');
    const newLines = newText.split('\n');
    
    const diffLines: string[] = [];
    let oldIndex = 0;
    let newIndex = 0;
    
    // Простое построчное сравнение
    while (oldIndex < oldLines.length || newIndex < newLines.length) {
        const oldLine = oldLines[oldIndex];
        const newLine = newLines[newIndex];
        
        if (oldIndex >= oldLines.length) {
            // Остались только новые строки
            diffLines.push(`+${newLine}`);
            newIndex++;
        } else if (newIndex >= newLines.length) {
            // Остались только старые строки
            diffLines.push(`-${oldLine}`);
            oldIndex++;
        } else if (oldLine === newLine) {
            // Строки одинаковые
            diffLines.push(` ${oldLine}`);
            oldIndex++;
            newIndex++;
        } else {
            // Строки разные - находим следующее совпадение
            let foundMatch = false;
            
            // Ищем совпадение в ближайших строках
            for (let lookAhead = 1; lookAhead <= 3; lookAhead++) {
                if (oldIndex + lookAhead < oldLines.length && 
                    oldLines[oldIndex + lookAhead] === newLine) {
                    // Найдено совпадение - удаляем промежуточные строки
                    for (let i = 0; i < lookAhead; i++) {
                        diffLines.push(`-${oldLines[oldIndex + i]}`);
                    }
                    oldIndex += lookAhead;
                    foundMatch = true;
                    break;
                }
                
                if (newIndex + lookAhead < newLines.length && 
                    newLines[newIndex + lookAhead] === oldLine) {
                    // Найдено совпадение - добавляем промежуточные строки
                    for (let i = 0; i < lookAhead; i++) {
                        diffLines.push(`+${newLines[newIndex + i]}`);
                    }
                    newIndex += lookAhead;
                    foundMatch = true;
                    break;
                }
            }
            
            if (!foundMatch) {
                // Не найдено совпадение - считаем что строка изменилась
                diffLines.push(`-${oldLine}`);
                diffLines.push(`+${newLine}`);
                oldIndex++;
                newIndex++;
            }
        }
    }
    
    // Добавляем заголовок diff
    const header = `@@ -1,${oldLines.length} +1,${newLines.length} @@`;
    
    return [header, ...diffLines].join('\n');
}

/**
 * Определяет тип строки в diff
 * @param line - строка diff
 * @returns тип строки: 'added', 'removed', 'context', 'header'
 */
export function getDiffLineType(line: string): 'added' | 'removed' | 'context' | 'header' {
    if (line.startsWith('@@')) {
        return 'header';
    } else if (line.startsWith('+')) {
        return 'added';
    } else if (line.startsWith('-')) {
        return 'removed';
    } else {
        return 'context';
    }
}
