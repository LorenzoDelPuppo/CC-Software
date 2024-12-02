public class Main {
    public static void main(String[] args) {
        String text = "Vigenere";
        String key = "chiave";

        String encrypted = encrypt(text, key);
        System.out.println("Testo cifrato: " + encrypted);

        String decrypted = decrypt(encrypted, key);
        System.out.println("Testo decifrato: " + decrypted);
    }

    public static String encrypt(String text, String key) {
        StringBuilder result = new StringBuilder();
        key = key.toUpperCase();
        int keyIndex = 0;

        for (int i = 0; i < text.length(); i++) {
            char currentChar = text.charAt(i);

            // Controllo se è una lettera
            if (Character.isLetter(currentChar)) {
                // Calcolo spostamento
                int shift = key.charAt(keyIndex) - 'A';
                char encryptedChar;

                if (Character.isUpperCase(currentChar)) {
                    encryptedChar = (char) ((currentChar - 'A' + shift) % 26 + 'A'); //(char) si usa per convertire da int a chat
                } else {
                    encryptedChar = (char) ((currentChar - 'a' + shift) % 26 + 'a');
                }

                result.append(encryptedChar);

                // Passaggio al carattere successivo della chiave
                keyIndex = (keyIndex + 1) % key.length();
            } else {
                // Se non è una lettera lo aggiungo senza modifiche
                result.append(currentChar);
            }
        }
        return result.toString();
    }

    public static String decrypt(String text, String key) {
        StringBuilder result = new StringBuilder();
        key = key.toUpperCase();
        int keyIndex = 0;

        for (int i = 0; i < text.length(); i++) {
            char currentChar = text.charAt(i);

            // Controlla se il carattere è una lettera
            if (Character.isLetter(currentChar)) {
                // Calcola lo spostamento usando la chiave (inverso per decifrare)
                int shift = key.charAt(keyIndex) - 'A';
                char decryptedChar;

                if (Character.isUpperCase(currentChar)) {
                    decryptedChar = (char) ((currentChar - 'A' - shift + 26) % 26 + 'A');
                } else {
                    decryptedChar = (char) ((currentChar - 'a' - shift + 26) % 26 + 'a');
                }

                result.append(decryptedChar);

                // Passaggio al carattere successivo della chiave
                keyIndex = (keyIndex + 1) % key.length();
            } else {
                // Se non è una lettera lo aggiungo senza modifiche
                result.append(currentChar);
            }
        }
        return result.toString();
    }
}
