
content = open('resources/views/erp/products/edit.blade.php', 'r').read()
open_divs = content.count('<div')
close_divs = content.count('</div>')
print(f'Open divs: {open_divs}')
print(f'Close divs: {close_divs}')
print(f'Diff: {open_divs - close_divs}')
