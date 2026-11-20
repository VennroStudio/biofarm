export function listFromLines(value: string) {
  return value.split('\n').map((item) => item.trim()).filter(Boolean);
}
